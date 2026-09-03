<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required'], 'app' => ['required', 'in:employer,seeker']]);
        $user = User::where('email', $data['email'])->first(); $role = $data['app'] === 'employer' ? 'employer' : 'job-seeker';
        abort_unless($user && $user->hasRole($role) && Hash::check($data['password'], $user->password), 422, 'Invalid credentials for this app.');
        return response()->json(['token' => $user->createToken("{$role}-mobile", [$role])->plainTextToken, 'user' => $user, 'role' => $role]);
    }

    public function registerSeeker(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:users'], 'phone' => ['required', 'string', 'max:32', 'unique:users'], 'country_code' => ['required', 'string', 'size:2'], 'password' => ['required', 'min:8']]);
        $user = User::create($data); $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id')); CandidateProfile::create(['user_id' => $user->id, 'country_code' => $data['country_code'], 'profile_completion' => 20]);
        return response()->json(['token' => $user->createToken('seeker-mobile', ['job-seeker'])->plainTextToken, 'user' => $user], 201);
    }


    public function registerEmployer(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:users'], 'phone' => ['required', 'string', 'max:32', 'unique:users'], 'country_code' => ['required', 'string', 'size:2'], 'password' => ['required', 'min:8'], 'company_name' => ['required', 'string', 'max:180']]);
        $user = User::create(collect($data)->only(['name', 'email', 'phone', 'country_code', 'password'])->all()); $user->roles()->attach(Role::where('slug', 'employer')->value('id'));
        $company = Company::create(['name' => $data['company_name'], 'email' => $data['email'], 'phone' => $data['phone'], 'country_code' => $data['country_code'], 'status' => 'pending']); $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);
        return response()->json(['token' => $user->createToken('employer-mobile', ['employer'])->plainTextToken, 'user' => $user, 'company' => $company], 201);
    }
}