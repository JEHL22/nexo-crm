<section
    x-data="{
        previewUrl: @js($user->profilePhotoUrl()),
        primaryColor: @js(old('crm_primary_color', $user->crmPrimaryColor())),
        secondaryColor: @js(old('crm_secondary_color', $user->crmSecondaryColor())),
        themeMode: @js(old('crm_theme_mode', $user->crmThemeMode())),
        updatePreview(event) {
            const [file] = event.target.files;
            if (!file) {
                return;
            }

            this.previewUrl = URL.createObjectURL(file);
        }
    }"
>
    <header>
        <h2 class="text-xl font-semibold text-slate-900">
            Personaliza tu CRM
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Elige tu foto, tus colores y el tema visual con el que quieres trabajar.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-sm font-semibold text-slate-900">Foto de perfil</div>
                <p class="mt-1 text-xs text-slate-500">Se mostrará en el sidebar y en tu cabecera del CRM.</p>

                <div class="mt-5 flex items-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Foto de perfil" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!previewUrl">
                            <span class="text-lg font-semibold uppercase text-slate-500">{{ $user->initials() }}</span>
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <label for="profile_photo" class="inline-flex cursor-pointer items-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                            Subir foto
                        </label>
                        <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="updatePreview($event)">

                        @if($user->profilePhotoUrl())
                            <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-500">
                                <input type="checkbox" name="remove_profile_photo" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                                Quitar foto actual
                            </label>
                        @endif
                    </div>
                </div>

                <x-input-error class="mt-3" :messages="$errors->get('profile_photo')" />
                <x-input-error class="mt-2" :messages="$errors->get('remove_profile_photo')" />
            </div>

            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-base font-semibold text-slate-900">Tema visual</h3>
                        <p class="text-sm text-slate-500">Escoge si quieres ver tu CRM con fondo claro u oscuro.</p>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="cursor-pointer rounded-3xl border p-4 transition"
                               :class="themeMode === 'light' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                            <input type="radio" name="crm_theme_mode" value="light" class="sr-only" x-model="themeMode">
                            <div class="text-sm font-semibold">Tema claro</div>
                            <div class="mt-2 text-xs" :class="themeMode === 'light' ? 'text-white/75' : 'text-slate-500'">Más limpio, luminoso y similar al CRM actual.</div>
                        </label>

                        <label class="cursor-pointer rounded-3xl border p-4 transition"
                               :class="themeMode === 'dark' ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                            <input type="radio" name="crm_theme_mode" value="dark" class="sr-only" x-model="themeMode">
                            <div class="text-sm font-semibold">Tema oscuro</div>
                            <div class="mt-2 text-xs" :class="themeMode === 'dark' ? 'text-white/75' : 'text-slate-500'">Reduce el brillo y da un look más intenso para trabajo prolongado.</div>
                        </label>
                    </div>

                    <x-input-error class="mt-3" :messages="$errors->get('crm_theme_mode')" />
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-1">
                <h3 class="text-base font-semibold text-slate-900">Personaliza los colores del CRM</h3>
                <p class="text-sm text-slate-500">Estos colores se aplicarán solo a tu navegación, acciones principales y acentos del sistema.</p>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <x-input-label for="crm_primary_color" value="Color principal" />
                    <div class="mt-2 flex items-center gap-3">
                        <input id="crm_primary_color_picker" type="color" x-model="primaryColor" class="h-12 w-16 cursor-pointer rounded-2xl border border-slate-200 bg-white p-1">
                        <x-text-input id="crm_primary_color" name="crm_primary_color" type="text" class="block w-full" x-model="primaryColor" />
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('crm_primary_color')" />
                </div>

                <div>
                    <x-input-label for="crm_secondary_color" value="Color secundario" />
                    <div class="mt-2 flex items-center gap-3">
                        <input id="crm_secondary_color_picker" type="color" x-model="secondaryColor" class="h-12 w-16 cursor-pointer rounded-2xl border border-slate-200 bg-white p-1">
                        <x-text-input id="crm_secondary_color" name="crm_secondary_color" type="text" class="block w-full" x-model="secondaryColor" />
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('crm_secondary_color')" />
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Vista previa</div>
                    <div class="mt-4 rounded-2xl p-4 text-white shadow-sm" :style="`background: linear-gradient(135deg, ${primaryColor}, ${secondaryColor});`">
                        <div class="text-sm font-semibold">Mi CRM personalizado</div>
                        <div class="mt-2 text-xs text-white/80">Botones, navegación y acentos usarán esta combinación.</div>
                    </div>
                    <div class="mt-3 rounded-2xl border p-4 transition" :class="themeMode === 'dark' ? 'border-slate-700 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700'">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em]" :class="themeMode === 'dark' ? 'text-slate-400' : 'text-slate-500'">Modo</div>
                        <div class="mt-2 text-sm font-semibold" x-text="themeMode === 'dark' ? 'Tema oscuro' : 'Tema claro'"></div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Sugerencia</div>
                    <div class="mt-3 text-sm leading-6 text-slate-600">
                        Si quieres mantener el estilo actual del sistema, usa tonos oscuros para el color principal y uno más brillante para el secundario.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar cambios</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Guardado.</p>
            @endif
        </div>
    </form>
</section>
