@extends('layouts.app')

@section('content')
  <div class="text-black md:min-h-screen px-4 sm:px-6 py-40 md:py-24 md:grid md:place-items-center lg:px-8">
    <div class="max-w-max mx-auto">
      <main class="sm:flex">
        <p class="flex md:justify-center md:items-center text-4xl font-extrabold text-primary sm:text-5xl">404</p>
        <div class="sm:ml-6">
          <div class="sm:border-l sm:border-gray-400 sm:pl-6">
            <h1 class="text-4xl font-extrabold text-black tracking-tight sm:text-5xl">Pagina niet gevonden</h1>
            <p class="mt-1 md:mt-3 text-base text-black">Helaas, de pagina die je zoekt is niet gevonden. Weet je zeker dat je de juiste URL hebt?</p>
            <div class="mt-4">
              <x-button href="/" type="primary">Terug naar home</x-button>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

@endsection
