
@extends('admin.layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="login-card">

        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Please login to your account.</p>
        </div>

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf

            <div class="input-group">
                <label for="email">Email Address</label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Enter your email"
                    autocomplete="username"
                    required
                    autofocus
                >

                @error('email')
                <small class="text-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="input-group">
                <label for="password">Password</label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                @error('password')
                <small class="text-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="remember">
                <label for="remember">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                    >

                    Remember Me
                </label>
            </div>

            <button type="submit" class="btn-login">
                Login
            </button>
        </form>

    </div>
@endsection

