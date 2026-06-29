import type { ReactNode } from 'react';
import AuthShell from '@/Components/Auth/AuthShell';

type AuthLayoutProps = {
    children: ReactNode;
};

export default function AuthLayout({ children }: AuthLayoutProps) {
    return <AuthShell>{children}</AuthShell>;
}
