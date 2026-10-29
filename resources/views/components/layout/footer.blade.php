<footer class="border-t dark:border-zinc-800 border-zinc-100 mt-44 lg:min-h-[250px] min-h-full relative">
    <div class="max-w-7xl mx-auto flex lg:flex-row flex-col items-center lg:justify-between justify-center gap-y-4 md:px-16 px-6 py-16">
        <div class="flex md:flex-row flex-col items-center gap-x-2">
            <h3 class="font-inter">Feito com:</h3>
            <ul class="flex items-center gap-x-2 text-sm dark:text-zinc-600 text-zinc-400 md:mt-0 mt-3">
                <li><a href="https://laravel.com" rel="noreferrer noopener" target="_blank" class="dark:text-white text-zinc-600 hover:underline">Laravel</a></li>
                <li><a href="https://tailwindcss.com" rel="noreferrer noopener" target="_blank" class="dark:text-white text-zinc-600 hover:underline">Tailwind CSS</a></li>
            </ul>
        </div>
        <div class="flex flex-col lg:items-end items-center lg:text-start text-center">
            <small class="text-zinc-500">© {{ now()->year }} José Carlos · Todos os direitos reservados</small>
        </div>
    </div>
</footer>
