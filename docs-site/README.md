# INMACOM MIS — Documentation Site

This directory contains the **INMACOM MIS documentation website**, built with [Next.js](https://nextjs.org/).

It provides a browsable, rendered version of the documentation for the Incomati Basin Management Information System.

---

## Running Locally

```bash
cd docs-site
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) to view the site.

---

## Source Pages

Documentation source files live in `docs-site/app/`. They are written in MDX and correspond to the markdown files in the root [`docs/`](../docs/) directory.

For the raw markdown documentation (architecture, deployment, roles, API routes, GIS workflow, database schema), see the [`docs/`](../docs/) folder at the repository root.

---

## Building for Production

```bash
npm run build
npm run start
```

---

## Related

- [Repository README](../README.md)
- [Architecture](../docs/architecture.md)
- [Deployment Guide](../docs/deployment.md)
- [Roles & Permissions](../docs/roles-and-permissions.md)
