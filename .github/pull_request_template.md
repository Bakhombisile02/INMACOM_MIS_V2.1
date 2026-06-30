## Summary

<!-- Briefly describe what this PR changes and why. Link to the relevant issue if applicable. Closes #<issue> -->

---

## Type of Change

- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that changes existing behaviour)
- [ ] Documentation update
- [ ] Chore / dependency update / refactor

---

## Checklist

### Code Quality
- [ ] `vendor/bin/pint` passes with no changes (`php linting/formatting`)
- [ ] `npm run build` passes with no TypeScript errors
- [ ] `composer test` passes — all existing tests still green
- [ ] No debug statements, `dd()`, `console.log()`, or commented-out code left in

### Internationalisation
- [ ] All new user-facing strings have keys in **both** `resources/js/locales/en/` and `resources/js/locales/pt/`
- [ ] No strings are hardcoded in TSX or Blade templates

### Database
- [ ] New migrations use UUID primary keys (`$table->uuid('id')->primary()`)
- [ ] Migration has a correct `down()` method
- [ ] Reference data changes are in the seeder `TODO` blocks, not ad-hoc migrations

### Security
- [ ] No secrets, API keys, or credentials are committed
- [ ] File uploads are validated (MIME type, size)
- [ ] New routes that require role protection have the correct `middleware('role:...')` guard

### Documentation
- [ ] `CHANGELOG.md` updated under `[Unreleased]`
- [ ] Relevant `docs/` files updated if architecture or routes changed

---

## Testing Notes

<!-- Describe how you tested this change. Include steps, test data, or screenshots. -->

---

## Screenshots (if applicable)

<!-- Before / After screenshots for UI changes -->
