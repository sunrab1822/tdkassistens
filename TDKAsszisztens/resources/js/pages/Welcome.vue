<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { LogIn, Mail, ShieldCheck } from 'lucide-vue-next';
</script>

<template>
    <Head title="TDK Asszisztens">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen w-full overflow-hidden bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <!-- Bal panel: hero kép + bemutatkozás -->
        <div class="relative hidden w-1/2 flex-col p-8 lg:flex">
            <div class="absolute inset-0">
                <img src="/images/ai-hero.jpg" alt="AI asszisztens" class="h-full w-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 to-black/50"></div>
            </div>
            <div class="relative z-10 flex grow select-none flex-col justify-center text-white">
                <div class="mb-4 h-1.5 w-[60px] rounded-sm bg-blue-500"></div>
                <h2 class="mb-4 mt-0 text-3xl font-semibold text-white/90">TDK AI asszisztense</h2>
                <p class="max-w-[35rem] text-xl leading-relaxed text-white/70">
                    Az asszisztens mesterséges intelligencia segítségével támogatja a tudományos diákköri munkát: segít a témaválasztásban, az
                    irodalomkutatásban, a dolgozat felépítésében és a konferenciára való felkészülésben. Kérdezzen természetes nyelven, és kapjon
                    azonnali, releváns válaszokat.
                </p>
            </div>
        </div>

        <!-- Jobb panel: bejelentkezés -->
        <div class="relative flex w-full flex-col items-center justify-center p-4 md:p-6 lg:w-1/2">
            <div class="flex w-full max-w-[28rem] flex-col gap-4">
                <div class="mb-4 flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#1b1b18] text-white dark:bg-[#eeeeec] dark:text-[#1b1b18]">
                        <AppLogoIcon class="h-9 w-9 fill-current" />
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="mb-2 text-3xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Jelentkezzen be a folytatáshoz</h3>
                    <p class="m-0 leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                        Kérjük, jelentkezzen be a fiókjával az AI asszisztens használatához.
                    </p>
                </div>
                <div class="flex flex-col gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 p-3 text-lg font-semibold text-white transition-colors hover:bg-blue-700"
                    >
                        <LogIn class="h-5 w-5" />
                        Tovább az asszisztenshez
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 p-3 text-lg font-semibold text-white transition-colors hover:bg-blue-700"
                        >
                            <LogIn class="h-5 w-5" />
                            Bejelentkezés
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-[#19140035] p-3 text-lg font-semibold text-[#1b1b18] transition-colors hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            Regisztráció
                        </Link>
                    </template>
                </div>
                <div class="mt-4">
                    <div class="relative flex items-center justify-center">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-[#e3e3e0] dark:border-[#3E3E3A]"></div>
                        </div>
                        <span class="relative bg-[#FDFDFC] px-2 text-sm font-medium text-[#706f6c] dark:bg-[#0a0a0a] dark:text-[#A1A09A]">
                            Segítség és Információ
                        </span>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]"></div>
                </div>
            </div>
        </div>
    </div>
</template>
