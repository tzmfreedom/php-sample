import { Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { useStatelessForm } from '@/utils/form';

interface ResetPasswordProps {
    token: string;
    email: string;
    errors?: Record<string, string>;
}

export default function ResetPassword({ token, email, errors = {} }: ResetPasswordProps) {
    const { processing, action } = useStatelessForm(
        route('password.store'), 
        'post', 
        {
            successMessage: 'Password reset successfully!',
            resetFields: ['password', 'password_confirmation'],
        }
    );

    return (
        <AuthLayout title="Reset password" description="Please enter your new password below">
            <Head title="Reset password" />

            <form action={action}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            defaultValue={email}
                            className="mt-1 block w-full"
                            readOnly
                        />
                        <input type="hidden" name="token" value={token} />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            autoComplete="new-password"
                            className="mt-1 block w-full"
                            autoFocus
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autoComplete="new-password"
                            className="mt-1 block w-full"
                            placeholder="Confirm password"
                        />
                        <InputError message={errors.password_confirmation} className="mt-2" />
                    </div>

                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        Reset password
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
