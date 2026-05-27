import type { ReactNode } from 'react';
import { HeaderMenu } from '@/Components/UI/HeaderMenu';

type MainLayoutProps = {
    children: ReactNode;
};

export default function MainLayout({ children }: MainLayoutProps) {
    return (
        <>
            <HeaderMenu />
            {children}
        </>
    );
}
