import { router } from '@inertiajs/react';
import { useState, useTransition } from 'react';

interface FormSubmitOptions {
    preserveScroll?: boolean;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    resetFields?: string[];
}

interface UseFormOptions {
    successDuration?: number;
    resetFields?: string[];
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
}

// React 19のuseTransitionを使用した統合フォームフック
export function useStatelessForm(url: string, method: 'get' | 'post' | 'put' | 'patch' | 'delete' = 'post', options: UseFormOptions = {}) {
    const [isPending, startTransition] = useTransition();
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    
    const {
        successDuration = 2000,
        resetFields = [],
        onSuccess,
        onError
    } = options;

    const setSuccess = () => {
        setRecentlySuccessful(true);
        setTimeout(() => setRecentlySuccessful(false), successDuration);
    };

    // React 19のaction関数
    const action = (formData: FormData) => {
        startTransition(async () => {
            const data = Object.fromEntries(formData);
            
            const routerMethod = router[method].bind(router);
            
            try {
                await new Promise<void>((resolve, reject) => {
                    routerMethod(url, data, {
                        preserveScroll: true,
                        onSuccess: () => {
                            // 指定されたフィールドをリセット
                            if (resetFields.length > 0) {
                                resetFields.forEach(fieldName => {
                                    const field = document.querySelector(`[name="${fieldName}"]`) as HTMLInputElement;
                                    if (field) field.value = '';
                                });
                            }
                            setSuccess();
                            onSuccess?.();
                            resolve();
                        },
                        onError: (errors) => {
                            // エラーがあるフィールドをクリア＆フォーカス
                            const errorFields = Object.keys(errors);
                            if (errorFields.length > 0) {
                                const firstErrorField = document.querySelector(`[name="${errorFields[0]}"]`) as HTMLInputElement;
                                if (firstErrorField) {
                                    firstErrorField.focus();
                                }
                            }
                            onError?.(errors);
                            reject(new Error('Form submission failed'));
                        }
                    });
                });
            } catch (error) {
                // エラーハンドリングは既にonErrorで行われている
            }
        });
    };

    return {
        processing: isPending,
        recentlySuccessful,
        action
    };
}

// 成功状態を管理するカスタムフック（後方互換性のため残す）
export function useRecentlySuccessful(duration: number = 2000) {
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);

    const setSuccess = () => {
        setRecentlySuccessful(true);
        setTimeout(() => setRecentlySuccessful(false), duration);
    };

    return { recentlySuccessful, setSuccess };
}

/**
 * ネイティブHTML FormをInertia.jsで送信するためのヘルパー関数
 */
export function submitForm(
    form: HTMLFormElement, 
    url: string, 
    method: 'get' | 'post' | 'put' | 'patch' | 'delete' = 'post',
    options: FormSubmitOptions = {}
) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const {
        preserveScroll = true,
        onSuccess,
        onError,
        resetFields = []
    } = options;

    const routerMethod = router[method].bind(router);
    
    routerMethod(url, data, {
        preserveScroll,
        onSuccess: () => {
            // 指定されたフィールドをリセット
            if (resetFields.length > 0) {
                resetFields.forEach(fieldName => {
                    const field = form.querySelector(`[name="${fieldName}"]`) as HTMLInputElement;
                    if (field) field.value = '';
                });
            } else {
                // 全フィールドをリセット
                form.reset();
            }
            onSuccess?.();
        },
        onError: (errors) => {
            // エラーがあるフィールドをクリア＆フォーカス
            const errorFields = Object.keys(errors);
            if (errorFields.length > 0) {
                const firstErrorField = form.querySelector(`[name="${errorFields[0]}"]`) as HTMLInputElement;
                if (firstErrorField) {
                    firstErrorField.focus();
                }
            }
            onError?.(errors);
        }
    });
}

/**
 * パスワードフィールド専用のエラーハンドリング
 */
export function handlePasswordErrors(errors: Record<string, string>) {
    if (errors.password) {
        const passwordInput = document.getElementById('password') as HTMLInputElement;
        const confirmInput = document.getElementById('password_confirmation') as HTMLInputElement;
        if (passwordInput) passwordInput.value = '';
        if (confirmInput) confirmInput.value = '';
        passwordInput?.focus();
    }

    if (errors.current_password) {
        const currentPasswordInput = document.getElementById('current_password') as HTMLInputElement;
        if (currentPasswordInput) {
            currentPasswordInput.value = '';
            currentPasswordInput.focus();
        }
    }
}