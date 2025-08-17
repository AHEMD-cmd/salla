<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Enter License Key</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Tailwind (Filament uses Tailwind) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Optional: Add Filament-style font --}}
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 space-y-6">
        <h2 class="text-center text-2xl font-semibold text-gray-800">
            من فضلك ادخل رمز الترخيص
        </h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 text-sm rounded-md px-4 py-2">
                {{ $errors->first('license') }}
            </div>
        @endif

        <form method="POST" action="{{ route('license.validate') }}" class="space-y-4">
            @csrf
            <input type="text" name="license" placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 placeholder-gray-400 text-sm" />

            <button type="submit"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-4 rounded-xl transition duration-200">
                ارسال
            </button>



        </form>
    </div>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            600: '#f59e0b',
                            700: '#ffa500'
                        }
                    }
                }
            }
        }
    </script>


</body>

</html>
