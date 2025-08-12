import { router } from '@inertiajs/react';
import { useTransition, useRef, useCallback, useState, useEffect } from 'react';
import { toast } from 'sonner';

interface UseFormOptions {
    successMessage?: string;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    withFormRef?: boolean; // formRefを返すかどうかのフラグ
}

// React 19のuseTransitionを使用した統合フォームフック
export function useStatelessForm(url: string, method: 'get' | 'post' | 'put' | 'patch' | 'delete' = 'post', options: UseFormOptions = {}) {
    const [isPending, startTransition] = useTransition();
    const formRef = useRef<HTMLFormElement>(null);
    const cancelTokenRef = useRef<{ cancel: VoidFunction }|null>(null);

    // 追加のstate管理
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);

    const {
        successMessage = 'Saved!',
        onSuccess,
        onError,
        withFormRef = false
    } = options;

    // recentlySuccessfulの管理用effect
    useEffect(() => {
        if (recentlySuccessful) {
            const timer = setTimeout(() => {
                setRecentlySuccessful(false);
            }, 2000);
            return () => clearTimeout(timer);
        }
    }, [recentlySuccessful]);

    // React 19のaction関数
    const action = (formData: FormData) => {
        startTransition(async () => {
            const routerMethod = router[method].bind(router);
            setErrors({});

            try {
                await new Promise<void>((resolve, reject) => {
                    routerMethod(url, formData, {
                        preserveScroll: true,
                        onCancelToken: (cancelToken) => {
                            cancelTokenRef.current = cancelToken;
                        },
                        onSuccess: () => {
                            cancelTokenRef.current = null;
                            setRecentlySuccessful(true);
                            toast.success(successMessage);
                            onSuccess?.();
                            resolve();
                        },
                        onError: (errors) => {
                            cancelTokenRef.current = null;
                            setErrors(errors);
                            onError?.(errors);
                            reject(new Error('Form submission failed'));
                        }
                    });
                });
            } catch {
                // エラーハンドリングは既にonErrorで行われている
            }
        });
    };

    // フォームをリセットするヘルパー関数
    const resetForm = useCallback(() => {
        if (formRef.current) {
            formRef.current.reset();
        }
        setErrors({});
        setRecentlySuccessful(false);
    }, []);

    // キャンセル関数
    const cancel = useCallback(() => {
        if (cancelTokenRef.current) {
            cancelTokenRef.current.cancel();
        }
    }, []);

    // エラーを手動で設定する関数
    const setError = useCallback((field: string, message: string) => {
        setErrors(prev => ({ ...prev, [field]: message }));
    }, []);

    return {
        processing: isPending,
        errors,
        recentlySuccessful,
        action,
        cancel,
        setError,
        ...(withFormRef && { formRef, resetForm })
    };
}


// refユーティリティ関数（コンポーネント外で使用）
export const fieldUtils = {
    clearField: (fieldRef: React.RefObject<HTMLInputElement | null>) => {
        if (fieldRef.current) {
            fieldRef.current.value = '';
        }
    },

    focusField: (fieldRef: React.RefObject<HTMLInputElement | null>) => {
        if (fieldRef.current) {
            fieldRef.current.focus();
        }
    },

    clearAndFocus: (fieldRef: React.RefObject<HTMLInputElement | null>) => {
        if (fieldRef.current) {
            fieldRef.current.value = '';
            fieldRef.current.focus();
        }
    }
};

