import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Search, UserCircle } from 'lucide-react';
import { useState } from 'react';

type Props = App.Data.UsersPageData;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ユーザ管理',
        href: '/users',
    },
];

export default function UsersIndex({ data }: Props) {
    const { users, pagination, filters } = data;
    const [searchTerm, setSearchTerm] = useState(filters.search ?? '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/users', { search: searchTerm }, { preserveState: true });
    };

    const handlePageChange = (url: string | null) => {
        if (url) {
            router.get(url, {}, { preserveState: true });
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ユーザ一覧" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">ユーザ一覧</h1>
                        <p className="text-muted-foreground">
                            登録されているユーザの一覧を表示します
                        </p>
                    </div>
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative">
                            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="ユーザ名またはメールアドレスで検索"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-8 w-64"
                            />
                        </div>
                        <Button type="submit">検索</Button>
                    </form>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {users.map((user) => (
                        <Card key={user.id}>
                            <CardHeader className="pb-3">
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <UserCircle className="h-8 w-8 text-muted-foreground" />
                                        <div>
                                            <CardTitle className="text-base">{user.name}</CardTitle>
                                            <CardDescription className="text-sm">
                                                {user.email}
                                            </CardDescription>
                                        </div>
                                    </div>
                                    <Badge
                                        variant={user.email_verified_at ? 'default' : 'secondary'}
                                        className="text-xs"
                                    >
                                        {user.email_verified_at ? '認証済み' : '未認証'}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="pt-0">
                                <div className="text-sm text-muted-foreground space-y-1">
                                    <div>登録日: {formatDate(user.created_at)}</div>
                                    <div>更新日: {formatDate(user.updated_at)}</div>
                                    {user.email_verified_at && (
                                        <div>
                                            メール認証日: {formatDate(user.email_verified_at)}
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {users.length === 0 && (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <UserCircle className="h-12 w-12 text-muted-foreground mb-4" />
                        <h2 className="text-xl font-semibold mb-2">ユーザが見つかりません</h2>
                        <p className="text-muted-foreground">
                            {filters.search
                                ? '検索条件に一致するユーザが見つかりませんでした'
                                : 'まだユーザが登録されていません'}
                        </p>
                    </div>
                )}

                {pagination.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            {pagination.from && pagination.to
                                ? `${pagination.from} - ${pagination.to} 件目 (全 ${pagination.total} 件)`
                                : `全 ${pagination.total} 件`}
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handlePageChange(pagination.prev_page_url)}
                                disabled={!pagination.prev_page_url}
                            >
                                前へ
                            </Button>
                            <div className="flex items-center gap-1">
                                {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map(
                                    (page) => (
                                        <Button
                                            key={page}
                                            variant={
                                                page === pagination.current_page
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            onClick={() =>
                                                handlePageChange(`/users?page=${page}`)
                                            }
                                            className="min-w-[2rem]"
                                        >
                                            {page}
                                        </Button>
                                    ),
                                )}
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handlePageChange(pagination.next_page_url)}
                                disabled={!pagination.next_page_url}
                            >
                                次へ
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}