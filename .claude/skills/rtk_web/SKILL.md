```markdown
# rtk_web Development Patterns

> Auto-generated skill from repository analysis

## Overview

This skill teaches the core development patterns, workflows, and conventions used in the `rtk_web` repository. The project is primarily JavaScript (with significant PHP and SQL), focused on web application features such as authentication, vouchers, maps, account management, and documentation. No major JavaScript framework is used; code is organized by feature and follows clear conventions for file naming, imports, and exports. This guide will help you contribute effectively and maintain consistency across the codebase.

---

## Coding Conventions

**File Naming**
- Use `snake_case` for files and folders.
  - Example: `landing_page.js`, `rtk_account_handlers.php`

**Import Style**
- Use relative imports in JavaScript.
  - Example:
    ```js
    import { showPopup } from './popup_helper.js';
    ```

**Export Style**
- Use named exports in JavaScript.
  - Example:
    ```js
    export function showPopup() { ... }
    export const AVATAR_SIZE = 48;
    ```

**Commit Patterns**
- Commit messages are freeform, often without strict prefixes.
- Average length: ~27 characters.

**Other Conventions**
- CSS and JS are organized by feature in `public/assets/`.
- PHP logic is grouped by domain (e.g., `private/action/auth/`).
- Documentation is in Markdown under `docs/`.

---

## Workflows

### Feature Development: Landing Page

**Trigger:** When adding or updating UI features on the landing page  
**Command:** `/update-landing-feature`

1. Edit or add images in `public/assets/img/landingpage/`
2. Update CSS in `public/assets/css/landing.css`
3. Update or add JavaScript in `public/assets/js/landing.js`
4. Modify PHP logic or markup in `public/landing.php`

**Example:**
```js
// public/assets/js/landing.js
import { showPopup } from './popup_helper.js';

function displayWelcome() {
  showPopup('Welcome to RTK!');
}
```

---

### Auth OTP Feature Workflow

**Trigger:** When adding or improving OTP-based authentication or password reset  
**Command:** `/update-otp-auth`

1. Create or update migration in `db/migrations/` (e.g., `*_add_otp_verification.sql`)
2. Update or add PHP logic in `private/action/auth/` (e.g., `process_otp.php`)
3. Update helper utilities in `private/utils/` (e.g., `otp_helper.php`)
4. Update documentation in `docs/doc_for_user/otp_authentication.md`
5. Update frontend pages in `public/pages/auth/` (e.g., `reset-password-otp.php`)
6. Update CSS in `public/assets/css/home.css`
7. Update handler logic in `public/handlers/action_handler.php`
8. Update homepage if needed

**Example:**
```php
// private/action/auth/process_otp.php
include_once '../../utils/otp_helper.php';

if (verify_otp($_POST['otp'], $_POST['user_id'])) {
    // Success logic
}
```

---

### Voucher Feature Workflow

**Trigger:** When adding or improving voucher features in the purchase flow  
**Command:** `/update-voucher-feature`

1. Update documentation in `docs/` (e.g., `voucher_system.md`)
2. Create or update migration in `db/migrations/` (e.g., `*_add_temp_phone_to_survey_account.sql`)
3. Update or add PHP logic in `private/action/purchase/` (e.g., `apply_voucher.php`)
4. Update helper utilities in `private/utils/` (e.g., `device_voucher_helper.php`)
5. Update PHP classes in `private/classes/` (e.g., `Voucher.php`)
6. Update error logging in `private/logs/error.log`
7. Update frontend JS and CSS in `public/assets/js/pages/purchase/` and `public/assets/css/pages/purchase/`
8. Update frontend pages in `public/pages/purchase/`

**Example:**
```php
// private/action/purchase/apply_voucher.php
include_once '../../utils/device_voucher_helper.php';

if (is_valid_voucher($_POST['voucher_code'])) {
    // Apply voucher logic
}
```

---

### Map Feature Development

**Trigger:** When adding, updating, or fixing map-related features  
**Command:** `/update-map-feature`

1. Update or add CSS in `public/assets/css/pages/map.css`
2. Update or add JS in `public/assets/js/pages/map.js`
3. Update or add PHP in `public/pages/map_display.php`
4. Update error logging in `private/logs/error.log`
5. Optionally, update documentation or data files

**Example:**
```js
// public/assets/js/pages/map.js
import { renderMap } from './map_utils.js';

renderMap('map-container', { zoom: 10 });
```

---

### Account Management Feature Workflow

**Trigger:** When adding or updating RTK account management features  
**Command:** `/update-account-management`

1. Update or add PHP classes in `private/classes/` (e.g., `RtkAccount.php`)
2. Update or add handler logic in `public/handlers/rtk_account_handlers.php`
3. Update or add JS in `public/assets/js/pages/rtk/rtk_accountmanagement.js`
4. Update or add CSS in `public/assets/css/pages/rtk/rtk_account_update.css` and `rtk_accountmanagement.css`
5. Update frontend pages in `public/pages/rtk_accountmanagement.php`
6. Update error logging in `private/logs/error.log`
7. Optionally, update documentation in `docs/`

**Example:**
```php
// private/classes/RtkAccount.php
class RtkAccount {
    public function updatePermissions($userId, $permissions) {
        // Update logic
    }
}
```

---

### Documentation Update Workflow

**Trigger:** When updating or adding documentation for features, modules, or guides  
**Command:** `/update-docs`

1. Edit or add files in `docs/doc_for_user/`
2. Edit or add files in `docs/doc_manage_code/`
3. Edit or add files in `docs/newdoc_manage/`
4. Edit or add files in `docs/newdoc_user/`
5. Optionally, update related frontend files for consistency

**Example:**
```markdown
<!-- docs/doc_for_user/otp_authentication.md -->
# OTP Authentication
This guide explains how to use OTP for login and password reset.
```

---

## Testing Patterns

- Test files follow the pattern `*.test.*` (e.g., `voucher.test.js`)
- Testing framework is not clearly specified; check existing test files for style.
- Place tests alongside the code they cover or in dedicated test directories.

**Example:**
```js
// voucher.test.js
import { isValidVoucher } from './device_voucher_helper.js';

test('valid voucher returns true', () => {
  expect(isValidVoucher('ABC123')).toBe(true);
});
```

---

## Commands

| Command                    | Purpose                                             |
|----------------------------|-----------------------------------------------------|
| /update-landing-feature    | Add or update landing page UI features              |
| /update-otp-auth           | Implement or update OTP authentication features     |
| /update-voucher-feature    | Add or improve voucher logic in purchase flow       |
| /update-map-feature        | Add or update map display and related features      |
| /update-account-management | Add or update RTK account management features       |
| /update-docs               | Update or add user/developer documentation          |
```
