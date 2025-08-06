import { router } from '@inertiajs/react';
import { useTransition, useRef, useCallback } from 'react';
import { toast } from 'sonner';

interface UseFormOptions {
    successMessage?: string;
    resetFields?: string[];
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    useRefs?: boolean; // refベースモードを有効にするフラグ
}

// React 19のuseTransitionを使用した統合フォームフック
export function useStatelessForm(url: string, method: 'get' | 'post' | 'put' | 'patch' | 'delete' = 'post', options: UseFormOptions = {}) {
    const [isPending, startTransition] = useTransition();
    const formRef = useRef<HTMLFormElement>(null);
    
    const {
        successMessage = 'Saved!',
        resetFields = [],
        onSuccess,
        onError,
        useRefs = false
    } = options;

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
                            // トーストで成功メッセージを表示
                            toast.success(successMessage);
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
    }, []);

    return {
        processing: isPending,
        action,
        ...(useRefs && { formRef, resetForm })
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

