<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $branchId = $request->header('branch_id');
        
        if (!$branchId) {
            $branchId = 1;
            $request->headers->set('branch_id', $branchId);
        }
        
        try {
            $branchExists = DB::table('branches')->where('id', $branchId)->exists();
            
            if (!$branchExists) {
                return response()->json([
                    'errors' => [
                        [
                            'code' => 'auth-001',
                            'message' => 'Branch not found.'
                        ]
                    ]
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    [
                        'code' => 'auth-002', 
                        'message' => 'Database error.'
                    ]
                ]
            ], 500);
        }
        
        return $next($request);
    }
}