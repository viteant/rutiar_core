@component('mail::message')
    # Bienvenido a Rutiar

    Hola {{ $user->name }},

    Tu empresa **{{ $company->name }}** ha sido creada en Rutiar.

    Estos son tus datos de acceso iniciales:

    - **Correo:** {{ $user->email }}
    - **Contraseña temporal:** {{ $temporaryPassword }}

    Por seguridad, se te pedirá cambiar esta contraseña en tu primer inicio de sesión.

    @component('mail::button', ['url' => config('app.url').'/login'])
        Ir al panel de Rutiar
    @endcomponent

    Si no reconoces este acceso o crees que se creó por error, por favor contacta con el equipo de soporte.

    Saludos,
    {{ config('app.name') }}
@endcomponent
