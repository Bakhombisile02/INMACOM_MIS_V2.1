import type { Metadata } from "next";
import { Footer, Layout, Navbar } from "nextra-theme-docs";
import { Head } from "nextra/components";
import { getPageMap } from "nextra/page-map";
import "nextra-theme-docs/style.css";
import "./globals.css";

export const metadata: Metadata = {
  title: "INMACOM Documentation Portal",
  description: "Comprehensive technical documentation and user guide for the INMACOM water-resources management system.",
};

const LogoIcon = () => (
  <img
    src="/docs/images/inmacom-logo.png"
    alt="INMACOM logo"
    width="32"
    height="32"
    style={{ objectFit: "contain", flexShrink: 0 }}
  />
);

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const pageMap = await getPageMap();

  const logo = (
    <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
      <LogoIcon />
      <span style={{ fontSize: "1.2rem", fontWeight: 700, letterSpacing: "-0.02em" }}>
        INMACOM <span style={{ color: "#3b82f6" }}>MIS</span>
      </span>
      <span style={{ 
        fontSize: "0.75rem", 
        fontWeight: 500, 
        background: "rgba(59, 130, 246, 0.1)", 
        color: "#3b82f6", 
        padding: "2px 6px", 
        borderRadius: "4px" 
      }}>
        Documentation
      </span>
    </div>
  );

  return (
    <html lang="en" dir="ltr" suppressHydrationWarning>
      <Head />
      <body>
        <Layout
          pageMap={pageMap}
          navbar={
            <Navbar
              logo={logo}
              projectLink="https://github.com/Bakhombisile02/INMACOM_MIS_V2.1"
            />
          }
          footer={
            <Footer>
              <span>
                © {new Date().getFullYear()} INMACOM. Built for Transboundary Water Resources Management.
              </span>
            </Footer>
          }
        >
          {children}
        </Layout>
      </body>
    </html>
  );
}
