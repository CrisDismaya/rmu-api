<?php

namespace App\Http\Controllers\api_v1;

use App\Http\Controllers\api_v1\BaseController as BaseController;
use App\Http\Traits\helper;
use App\Models\branch;
use App\Models\FilesUploaded;
use App\Models\PhysicalInventoryDoc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use ZipArchive;

class PhysicalInventoryDocController extends BaseController
{
    use helper;

    const REQUIRED_DOCUMENT_TYPES = [
        [
            'id'    => 1,
            'name'  => 'inventory_cert',
            'label' => 'Certificate of Inventory',
            'type'  => ['pdf'],
        ],
        [
            'id'    => 2,
            'name'  => 'actual_pics',
            'label' => 'Actual Picture & Stencil',
            'type'  => ['pdf'],
        ],
        [
            'id'    => 3,
            'name'  => 'inventory_list',
            'label' => 'List of Inventory',
            'type'  => ['xls', 'xlsx'],
        ],
    ];

    public function createPhysicalInventoryDoc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module_id'    => 'required|exists:system_menu,id',
            'month'        => 'required|date_format:Y-m-d',
            'files'        => 'required|array|min:1',
            'reason'       => 'nullable|string|max:255',
            'files.*'      => 'required|file|mimes:pdf,xls,xlsx|max:10240',
            'doc_types'    => 'required|array|min:1',
            'doc_types.*'  => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        $user = Auth::user();

        try {
            $physicalInventory = PhysicalInventoryDoc::create([
                'branch_id'     => $user->branch,
                'selected_date' => $validated['month'],
                'reason'        => $validated['reason'] ?? null,
                'created_by'    => $user->id,
            ]);

            $matrix =  $this->ApprovalMatrixActivityLog($validated['module_id'], $physicalInventory->id);

            if ($matrix['status'] === 'error') {
                return response()->json([
                    'message' => 'Approval Matrix Error',
                    'error'   => $matrix['message'],
                ], 422);
            }

            // Update first approver or holder
            $physicalInventory->approved_by = $matrix['message'];
            $physicalInventory->save();

            $branch = branch::find($user->branch);
            if (!$branch) {
                return response()->json(['message' => 'Branch not found.'], 404);
            }

            $branchName = Str::slug($branch->name, '_');
            $yearMonth = Carbon::parse($validated['month'])->format('Y-m');
            $uniqueId = strtolower(uniqid());

            $relativePath = "image/physical_inventory/{$yearMonth}-{$branchName}";
            $fullPath = public_path($relativePath);

            if (!File::isDirectory($fullPath)) {
                File::makeDirectory($fullPath, 0755, true, true);
            }

            $fileCount = count($validated['files']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = $validated['files'][$i];
                $docTypeId = $validated['doc_types'][$i] ?? null;
                $name = strtolower($file->getClientOriginalName());
                $extension = strtolower($file->getClientOriginalExtension());

                // Validate docTypeId against allowed types
                $docType = collect(self::REQUIRED_DOCUMENT_TYPES)
                    ->firstWhere('id', $docTypeId);

                if (!$docType) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Invalid document type ID at index {$i}.",
                    ], 422);
                }

                if (!in_array($extension, $docType['type'])) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Invalid file type '{$extension}' for '{$docType['label']}' at index {$i}. Allowed: " . implode(', ', $docType['type']),
                    ], 422);
                }

                // Generate filename
                $fileName = "{$uniqueId}-{$name}";
                $file->move($fullPath, $fileName);

                // Update path in validated list (optional)
                $validated['files'][$i] = "{$relativePath}/{$fileName}";

                FilesUploaded::create([
                    'module_id' => $request->module_id,
                    'branch_id' => $user->branch,
                    'reference_id' => $physicalInventory->id,
                    'files_id' => $docType['id'],
                    'files_name' => $docType['label'],
                    'path' => "{$relativePath}/{$fileName}",
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Physical inventory document uploaded successfully.',
                'data' => $validated,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Server error.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPhysicalInventoryDocs(Request $request)
    {
        $user = Auth::user();
        $branchId = $user->branch;
        $module_id = $request->input('module_id');

        $query = DB::table('physical_inventory_docs as files')
            ->select([
                'files.id',
                'branch.name as branch_name',
                DB::raw("FORMAT(files.selected_date, 'MMMM yyyy') as month_year_name"),
                DB::raw("docs.total_count_uploaded"),
                DB::raw("
                    CASE
                        WHEN files.status = 0 THEN 'pending'
                        WHEN files.status = 1 THEN 'approved'
                        WHEN files.status = 2 THEN 'rejected'
                        ELSE ''
                    END as request_status
                "),
                DB::raw("CONCAT(approver.firstname, ' ', approver.lastname) as approver"),
                DB::raw("CONCAT(maker.firstname, ' ', maker.lastname) as requestor"),
                DB::raw("FORMAT(files.created_at, 'MMM dd, yyyy') as created_at"),
                'files.reason'
            ])
            ->joinSub(function ($subquery) use ($module_id) {
                $subquery->from('files_uploaded')
                    ->select('reference_id', DB::raw('COUNT(*) as total_count_uploaded'))
                    ->where('is_deleted', 0)
                    ->where('module_id', $module_id)
                    ->groupBy('reference_id');
            }, 'docs', 'docs.reference_id', '=', 'files.id')
            ->leftJoin('users as approver', 'files.approved_by', '=', 'approver.id')
            ->leftJoin('users as maker', 'files.created_by', '=', 'maker.id')
            ->leftJoin('branches as branch', 'files.branch_id', '=', 'branch.id');

            if ($user->userrole === 'Warehouse Custodian') {
                $query->where('files.branch_id', $branchId);
            }
            else if ($user->userrole === 'Administrator') {
                $query;
            }
            else {
                $query->where('files.approved_by', $user->id);
            }

        return DataTables::of($query)->make(true);
    }

    public function getPhysicalInventoryFiles(Request $request)
    {
        $user = Auth::user();
        $id = $request->query('id');
        $module_id = $request->query('module_id');

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Missing file reference ID.',
                'data' => [],
            ], 422);
        }

        $files = DB::table('files_uploaded')
            ->where('reference_id', $id)
            ->where('module_id', $module_id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'files_id', 'files_name', 'path', 'is_deleted']);

        return response()->json([
            'status' => true,
            'message' => 'File details fetched successfully.',
            'data' => $files
        ]);
    }

    public function downloadPhysicalInventory(Request $request)
    {
        $user = Auth::user();

        $id = $request->input('id');
        $isFolder = $request->input('isFolder');

        $physicalInventory = PhysicalInventoryDoc::find($id);
        if (!$physicalInventory) {
            return response()->json(['message' => 'Physical Inventory not found.'], 404);
        }

        $branch = Branch::find($user->branch);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found.'], 404);
        }

        $branchNameSlug = Str::slug($branch->name, '_');
        $yearMonth = Carbon::parse($physicalInventory->selected_date)->format('Y-m');

        $folderName = "{$yearMonth}-{$branchNameSlug}";
        $publicRelativeFolderPath = "image/physical_inventory/{$folderName}";
        $fullFolderPath = public_path($publicRelativeFolderPath);

        if (!File::exists($fullFolderPath)) {
            return response()->json(['error' => 'Folder not found.'], 404);
        }

        $zipFileName = "{$folderName}.zip";
        $zipFilePath = storage_path("app/temp_zips/{$zipFileName}");

        File::ensureDirectoryExists(storage_path('app/temp_zips'));

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = File::allFiles($fullFolderPath);

            foreach ($files as $file) {
                $absolutePath = $file->getRealPath();
                $filename = $file->getFilename();
                $zip->addFile($absolutePath, $folderName . '/' . $filename);
            }

            $zip->close();
        } else {
            return response()->json(['error' => 'Failed to create ZIP file.'], 500);
        }

        return response()->download($zipFilePath, $zipFileName, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$zipFileName}\""
        ])->deleteFileAfterSend(true);
    }

    public function submitApproverDecision(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                => 'required|exists:physical_inventory_docs,id',
            'decision'          => 'required|in:1,2', // 1 = approve, 2 = disapprove
            'reason'            => 'nullable|string|max:255',
            'current_module_id' => 'nullable|exists:system_menu,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            $physicalInventory = PhysicalInventoryDoc::find($validated['id']);
            $userId = Auth::user()->id;
            $decision = (int) $validated['decision'];
            $nextApprover = null;

            if ($decision === 1) {
                // APPROVED
                $nextApprover = $this->approverDecision($validated['current_module_id'] ?? null, $physicalInventory->id, $userId);
                if ($nextApprover === 0) {
                    $physicalInventory->status = 1; // Approved
                }
            } elseif ($decision === 2) {
                // DISAPPROVED
                $firstApprover = $this->disapprovedDecision($validated['current_module_id'] ?? null, $physicalInventory->id, $userId);
                $physicalInventory->status = 2; // Disapproved
                $nextApprover = $firstApprover;
            }

            $physicalInventory->approved_by = $nextApprover ?: ($decision === 1 && $nextApprover === 0 ? $userId : $nextApprover);
            $physicalInventory->approved_date = Carbon::now();
            $physicalInventory->remarks = ($decision === 2 || $nextApprover === 0) ? ($validated['reason'] ?? null) : null;
            $physicalInventory->save();

            DB::commit();

            return response()->json([
                'message' => 'Decision submitted successfully.',
                'status'  => true,
                'data'    => $validated,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
