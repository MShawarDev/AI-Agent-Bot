<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="Profile" subtitle="Manage your account details and security." />

        <x-ui.glass-card>@include('profile.partials.update-profile-information-form')</x-ui.glass-card>
        <x-ui.glass-card>@include('profile.partials.update-password-form')</x-ui.glass-card>
        <x-ui.glass-card>@include('profile.partials.delete-user-form')</x-ui.glass-card>
    </div>
</x-app-layout>
