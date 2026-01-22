<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MLB Fantasy Draft Helper</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-600">⚾ MLB Draft Helper</h1>
                <p class="text-gray-600 mt-2">Please sign in to continue</p>
            </div>

            @if($errors->has('credentials'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ $errors->first('credentials') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">
                        Username
                    </label>
                    <input type="text" 
                           name="username" 
                           id="username" 
                           value="{{ old('username') }}"
                           class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('username') border-red-500 @enderror"
                           placeholder="Enter your username"
                           required 
                           autofocus>
                    @error('username')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                        Password
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror"
                           placeholder="Enter your password"
                           required>
                    @error('password')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-sm mt-4">
            &copy; {{ date('Y') }} MLB Fantasy Draft Helper
        </p>
    </div>
</body>
</html>

