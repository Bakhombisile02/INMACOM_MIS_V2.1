import nextra from "nextra";
import type { NextConfig } from "next";
import path from "path";

const withNextra = nextra({
  // Nextra options
});

const nextConfig: NextConfig = {
  output: "export",
  basePath: "/docs",
  trailingSlash: true,
  images: {
    unoptimized: true,
  },
  reactStrictMode: true,
  webpack: (config) => {
    config.resolve.alias = {
      ...config.resolve.alias,
      "next-mdx-import-source-file": path.resolve(process.cwd(), "mdx-components.tsx"),
    };
    return config;
  },
  turbopack: {
    root: path.resolve(process.cwd()),
    resolveAlias: {
      "next-mdx-import-source-file": "./mdx-components.tsx",
    },
  },
};

export default withNextra(nextConfig);
