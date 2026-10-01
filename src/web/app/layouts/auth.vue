<!-- Centered card for sign-in screens: no navigation, no sidebar. -->
<template>
  <div class="flex min-h-dvh flex-col bg-default text-default">
    <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center gap-6 px-4 py-12">
      <header class="flex flex-col gap-1 text-center">
        <p class="text-xs font-semibold uppercase tracking-widest text-secondary">
          Provincial Government of Benguet
        </p>
        <p class="text-lg font-semibold text-highlighted">
          CSMF-MRS
        </p>
      </header>
      <slot />
    </main>
    <footer class="px-4 pb-6 text-center text-xs text-dimmed">
      Client Satisfaction Measurement Form Management and Reporting System
    </footer>
  </div>
</template>
