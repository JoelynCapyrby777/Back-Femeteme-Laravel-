<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     */
    public function register(Request $request)
    {
        // Validación de datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:10|max:100',
            'email' => 'required|string|email|min:10|max:50|unique:users',
            'password' => 'required|string|min:10|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Crear el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json(['message' => 'Usuario creado exitosamente', 'user' => $user], 201);
    }

    /**
     * Iniciar sesión y obtener token JWT
     */
    public function login(Request $request)
    {
        // Validación de entrada
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|min:10|max:50',
            'password' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

            return response()->json([
                'message' => 'Inicio de sesión exitoso',
                'token' => $token,
                'user' => Auth::user()
            ], 200);

        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo generar el token'], 500);
        }
    }

    /**
     * Obtener datos del usuario autenticado
     */
    public function obtenerUsuario()
    {
        try {
            if (!$user = Auth::user()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener usuario'], 500);
        }
    }

    /**
     * Cerrar sesión e invalidar token
     */
    public function logout()
    {
        try {
            JWTAuth::parseToken()->invalidate();
            return response()->json(['message' => 'Sesión cerrada exitosamente'], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Error al cerrar sesión'], 500);
        }
    }
}
