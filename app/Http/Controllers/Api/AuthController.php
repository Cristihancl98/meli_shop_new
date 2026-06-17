<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $token = JWTAuth::attempt($request->only('email', 'password'));

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas.',
                'data'    => null,
                'errors'  => [],
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $operatorRole = Role::where('name', 'operator')->first();
        if ($operatorRole) {
            $user->roles()->attach($operatorRole->id);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente.',
            'data'    => ['user' => $user->load('roles'), 'token' => $token, 'token_type' => 'bearer', 'expires_in' => config('jwt.ttl') * 60],
            'errors'  => [],
        ], 201);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente.',
            'data'    => null,
            'errors'  => [],
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return $this->respondWithToken($token);
    }

    public function profile(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate()->load('roles', 'mercadolibreAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Perfil obtenido.',
            'data'    => $user,
            'errors'  => [],
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo de recuperación.',
                'data'    => null,
                'errors'  => [],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Correo de recuperación enviado.',
            'data'    => null,
            'errors'  => [],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado.',
                'data'    => null,
                'errors'  => [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente.',
            'data'    => null,
            'errors'  => [],
        ]);
    }

    private function respondWithToken(string $token): JsonResponse
    {
        $user = JWTAuth::setToken($token)->toUser()->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa.',
            'data'    => [
                'user'       => $user,
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
            'errors' => [],
        ]);
    }
}
