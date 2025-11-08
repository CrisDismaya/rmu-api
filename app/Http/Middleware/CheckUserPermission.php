<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Auth;

class CheckUserPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permissionType, ?int $menuId = null)
    {
        $auth = Auth::user();

        $user = User::query()
            ->select(
                'user.id as user_id',
                'role.id as role_id'
            )
            ->from('users as user')
            ->join('user_role as role', 'user.userrole', '=', 'role.user_role_name')
            ->where('user.id', $auth->id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized access. Please log in to continue.'], 401);
        }

        // Fetch user permission record
        $permission = UserPermission::where('user_id', $user->user_id)
            ->where('role_id', $user->role_id)
            ->when($menuId, fn($q) => $q->where('menu_id', $menuId))
            ->first();

        if (!$permission) {
            return response()->json([
                'message' => "Access denied. No permission record found for this module (ID: {$menuId}).",
                'details' => [
                    'user_id' => $user->id,
                    'menu_id' => $menuId,
                    'required_permission' => $permissionType
                ]
            ], 403);
        }

        // Map permission type to column name
        $column = match ($permissionType) {
            'view' => 'view_permission',
            'add' => 'add_permission',
            'update' => 'update_permission',
            'approval' => 'approval_permission',
            default => null,
        };

        if (!$column) {
            return response()->json([
                'message' => "Invalid permission type '{$permissionType}'.",
                'suggestion' => 'Allowed types: view, add, update, approval.'
            ], 400);
        }

        if (!$column || $permission->$column != 1) {
            return response()->json([
                'message' => "Access denied. You do not have '{$permissionType}' permission for this module.",
                'details' => [
                    'user_id' => $user->id,
                    'menu_id' => $menuId,
                    'missing_permission' => $permissionType,
                    'available_permissions' => [
                        'view' => $permission->view_permission,
                        'add' => $permission->add_permission,
                        'update' => $permission->update_permission,
                        'approval' => $permission->approval_permission,
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }
}
