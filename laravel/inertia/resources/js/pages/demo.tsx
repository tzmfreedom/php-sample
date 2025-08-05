import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demo',
        href: '/demo',
    },
];

interface TodoItem {
    id: number;
    text: string;
    completed: boolean;
}

export default function Demo(props: App.Models.DemoData) {
    const [todos, setTodos] = useState<TodoItem[]>([
        { id: 1, text: 'Laravel + Inertia.js + Reactの環境を確認', completed: true },
        { id: 2, text: 'フロントエンドコンポーネントのテスト', completed: false },
        { id: 3, text: 'インタラクティブな機能の実装', completed: false },
    ]);
    const [newTodo, setNewTodo] = useState('');
    const [counter, setCounter] = useState(0);

    const addTodo = () => {
        if (newTodo.trim()) {
            setTodos([...todos, {
                id: Date.now(),
                text: newTodo,
                completed: false
            }]);
            setNewTodo('');
        }
    };

    const toggleTodo = (id: number) => {
        setTodos(todos.map(todo =>
            todo.id === id ? { ...todo, completed: !todo.completed } : todo
        ));
    };

    const deleteTodo = (id: number) => {
        setTodos(todos.filter(todo => todo.id !== id));
    };

    const completedCount = todos.filter(todo => todo.completed).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Demo - Inertia.js + React" />
            
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
                <div className="text-center mb-6">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        Inertia.js + React デモページ
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Laravel + Inertia.js + Reactの統合デモンストレーション
                    </p>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* カウンターセクション */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span>🔢</span>
                                リアクティブカウンター
                            </CardTitle>
                            <CardDescription>
                                React hooksを使ったシンプルな状態管理のデモ
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="text-center">
                                <div className="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-4">
                                    {counter}
                                </div>
                                <div className="flex gap-2 justify-center">
                                    <Button
                                        onClick={() => setCounter(counter - 1)}
                                        variant="outline"
                                        size="sm"
                                    >
                                        -1
                                    </Button>
                                    <Button
                                        onClick={() => setCounter(counter + 1)}
                                        size="sm"
                                    >
                                        +1
                                    </Button>
                                    <Button
                                        onClick={() => setCounter(0)}
                                        variant="outline"
                                        size="sm"
                                    >
                                        リセット
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* TODO リスト */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span>📝</span>
                                TODOリスト
                                <Badge variant="secondary">{completedCount}/{todos.length}</Badge>
                            </CardTitle>
                            <CardDescription>
                                リスト操作と状態更新のデモ
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                <div className="flex-1">
                                    <Label htmlFor="new-todo" className="sr-only">
                                        新しいTODO
                                    </Label>
                                    <Input
                                        id="new-todo"
                                        placeholder="新しいTODOを入力..."
                                        value={newTodo}
                                        onChange={(e) => setNewTodo(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && addTodo()}
                                    />
                                </div>
                                <Button onClick={addTodo} size="sm">
                                    追加
                                </Button>
                            </div>
                            
                            <div className="space-y-2 max-h-48 overflow-y-auto">
                                {todos.map((todo) => (
                                    <div
                                        key={todo.id}
                                        className="flex items-center gap-2 p-2 rounded border dark:border-gray-700"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={todo.completed}
                                            onChange={() => toggleTodo(todo.id)}
                                            className="rounded"
                                        />
                                        <span
                                            className={`flex-1 ${
                                                todo.completed
                                                    ? 'line-through text-gray-500'
                                                    : 'text-gray-900 dark:text-gray-100'
                                            }`}
                                        >
                                            {todo.text}
                                        </span>
                                        <Button
                                            onClick={() => deleteTodo(todo.id)}
                                            variant="outline"
                                            size="sm"
                                            className="px-2"
                                        >
                                            🗑️
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* 技術スタック紹介 */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <span>⚡</span>
                            技術スタック
                        </CardTitle>
                        <CardDescription>
                            このプロジェクトで使用されている主要な技術
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                                <div className="text-2xl mb-2">🔴</div>
                                <h3 className="font-semibold text-red-700 dark:text-red-300">Laravel</h3>
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    バックエンドフレームワーク
                                </p>
                            </div>
                            <div className="text-center p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                                <div className="text-2xl mb-2">🟣</div>
                                <h3 className="font-semibold text-purple-700 dark:text-purple-300">Inertia.js</h3>
                                <p className="text-sm text-purple-600 dark:text-purple-400">
                                    フロントエンド・バックエンド連携
                                </p>
                            </div>
                            <div className="text-center p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                <div className="text-2xl mb-2">🔵</div>
                                <h3 className="font-semibold text-blue-700 dark:text-blue-300">React</h3>
                                <p className="text-sm text-blue-600 dark:text-blue-400">
                                    フロントエンドライブラリ
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 装飾的な要素 */}
                <div className="relative min-h-[200px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />
                    <div className="relative z-10 flex items-center justify-center h-full">
                        <div className="text-center">
                            <div className="text-4xl mb-4">🚀</div>
                            <h3 className="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                デモページ完了！
                            </h3>
                            <p className="text-gray-600 dark:text-gray-400">
                                Laravel + Inertia.js + Reactの統合が正常に動作しています
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}