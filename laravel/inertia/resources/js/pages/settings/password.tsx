import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useStatelessForm, fieldUtils } from '@/utils/form';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: '/settings/password',
    },
];

interface PasswordProps {
    errors?: Record<string, string>;
    message?: string;
}

export default function Password({ errors = {}, message }: PasswordProps) {
    const currentPasswordRef = useRef<HTMLInputElement>(null);
    const passwordRef = useRef<HTMLInputElement>(null);
    const passwordConfirmationRef = useRef<HTMLInputElement>(null);
    
    const { processing, formRef, action } = useStatelessForm(
        route('password.update'), 
        'put', 
        {
            successMessage: 'Password updated successfully!',
            useRefs: true,
            onError: (errors) => {
                if (errors.password) {
                    fieldUtils.clearField(passwordRef);
                    fieldUtils.clearField(passwordConfirmationRef);
                    fieldUtils.focusField(passwordRef);
                }
                if (errors.current_password) {
                    fieldUtils.clearAndFocus(currentPasswordRef);
                }
            }
        }
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Password settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Update password" description="Ensure your account is using a long, random password to stay secure" />

                    <form ref={formRef} action={action} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">Current password</Label>

                            <Input
                                ref={currentPasswordRef}
                                id="current_password"
                                name="current_password"
                                type="password"
                                className="mt-1 block w-full"
                                autoComplete="current-password"
                                placeholder="Current password"
                            />

                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">New password</Label>

                            <Input
                                ref={passwordRef}
                                id="password"
                                name="password"
                                type="password"
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                placeholder="New password"
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>

                            <Input
                                ref={passwordConfirmationRef}
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                placeholder="Confirm password"
                            />

                            <InputError message={errors.password_confirmation} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button type="submit" disabled={processing}>Save password</Button>

                            {message && (
                                <p className="text-sm text-green-600">{message}</p>
                            )}
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
