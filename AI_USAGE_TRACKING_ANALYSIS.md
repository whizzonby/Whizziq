# AI Usage Tracking Analysis

## Executive Summary

This analysis examines how AI usage is tracked, how it integrates with subscription plans, and identifies issues that need to be addressed. The system currently has **disconnects** between the hardcoded plan limits and the metadata-based system we implemented.

---

## 1. Current Usage Tracking Flow

### 1.1 How Usage is Tracked

**Location:** `app/Services/OpenAIService.php` → `app/Services/AIUsageService.php`

**Flow:**
1. User makes AI request (e.g., generate email)
2. `OpenAIService::chat()` is called
3. `AIUsageService::canMakeRequest()` checks if user can make request
4. If allowed, request is made to OpenAI API
5. After successful response, `AIUsageService::logUsage()` logs the usage
6. Usage is stored in `ai_usage_logs` table

**Usage Log Structure:**
- `user_id` - User who made the request
- `feature` - Feature name (e.g., 'general', 'email_generation', 'document_analysis')
- `action` - Action type (e.g., 'generate', 'improve', 'analyze')
- `tokens_used` - Number of tokens consumed
- `cost_cents` - Estimated cost in cents
- `prompt_summary` - First 200 chars of prompt
- `metadata` - Additional context (JSON)
- `requested_at` - Timestamp of request

### 1.2 Usage Tracking Points

✅ **Working Correctly:**
- Usage is logged after successful API calls
- Daily usage is tracked correctly (counts requests per day)
- Feature-specific usage is tracked
- Cache is properly invalidated after logging
- Cost estimation is calculated

---

## 2. Plan Limit System Issues

### 2.1 ❌ **CRITICAL: Hardcoded Plan Names**

**Problem:** `AIUsageService` uses hardcoded plan names that don't match actual plan names.

**Location:** `app/Services/AIUsageService.php:13-38`

```php
const LIMITS = [
    'basic' => [  // ❌ System uses 'basic'
        'daily_limit' => 20,
        // ...
    ],
    'pro' => [
        'daily_limit' => 75,
        // ...
    ],
    'premium' => [
        'daily_limit' => 200,
        // ...
    ],
];
```

**Actual Plan Names:**
- `starter` (not 'basic')
- `pro` ✅ (matches)
- `premium` ✅ (matches)

**Impact:** Users on "Starter" plan are treated as "basic" plan, which may work by coincidence but is fragile.

### 2.2 ❌ **CRITICAL: Plan Name Detection is Fragile**

**Location:** `app/Services/AIUsageService.php:254-277`

```php
protected function getUserPlanName(User $user): string
{
    // Uses string matching on plan slug
    if (str_contains(strtolower($planSlug), 'premium')) {
        return 'premium';
    } elseif (str_contains(strtolower($planSlug), 'pro')) {
        return 'pro';
    } elseif (str_contains(strtolower($planSlug), 'basic')) {
        return 'basic';  // ❌ Never matches 'starter'
    }
    return 'basic';  // ❌ Defaults to 'basic' for starter users
}
```

**Issues:**
1. String matching is fragile (e.g., "pro" matches "professional")
2. "Starter" plan never matches, always defaults to 'basic'
3. Doesn't use subscription metadata system we implemented

### 2.3 ❌ **CRITICAL: Hardcoded Limits Instead of Metadata**

**Problem:** Limits are hardcoded in `AIUsageService::LIMITS` instead of reading from subscription metadata.

**Current System:**
- Hardcoded: `'basic' => ['daily_limit' => 20]`
- Metadata has: `"ai_daily_limit": "20"` in plan metadata

**Impact:**
- Changes to plan metadata don't affect AI limits
- Two sources of truth (hardcoded vs metadata)
- Not aligned with the simple feature system we implemented

**What Should Happen:**
- Read `ai_daily_limit` from subscription metadata (like we do with `hasFeature()`)
- Use `User::subscriptionProductMetadata()` to get limits
- Fallback to defaults only if metadata is missing

---

## 3. Feature Name Tracking Issues

### 3.1 ❌ Missing Feature Names in Email Generation

**Location:** `app/Services/OpenAIService.php:229-288`

**Problem:** `generateEmail()` and `improveEmail()` don't pass a `feature` name to `chat()`.

```php
public function generateEmail(...): ?array
{
    $response = $this->chat([...], [
        // ❌ No 'feature' => 'email_generation' passed
        'temperature' => 0.7,
        'max_tokens' => 800,
    ]);
}
```

**Result:**
- Defaults to `'general'` feature (line 28: `$feature = $options['feature'] ?? 'general'`)
- All email generation tracked as 'general', not 'email_generation'
- Can't track email-specific usage separately
- Can't enforce email-specific limits

**What Should Happen:**
```php
$response = $this->chat([...], [
    'feature' => 'email_generation',  // ✅ Track as email feature
    'action' => 'generate',
    'temperature' => 0.7,
    'max_tokens' => 800,
]);
```

### 3.2 Feature Name Inconsistencies

**Current Feature Names Used:**
- `'general'` - Default fallback
- `'document_analysis'` - Document analysis (has special limit check)
- `'email_generation'` - Not actually used (should be)

**Metadata Keys Available:**
- `ai_email_features` - Feature flag (true/false)
- `ai_daily_limit` - Daily limit
- `ai_document_analysis_limit` - Document-specific limit

**Issue:** Feature names in code don't align with metadata keys.

---

## 4. Integration with Simple Feature System

### 4.1 ✅ Feature Access Check Works

**Location:** `app/Filament/Dashboard/Pages/EmailComposerPage.php:63`

```php
$hasAIEmailFeatures = auth()->user()?->hasFeature('ai_email_features') ?? false;
```

**Status:** ✅ Correctly uses `hasFeature()` method from User model
**Result:** UI correctly shows/hides AI features based on subscription

### 4.2 ❌ Usage Limit Check Doesn't Use Metadata

**Location:** `app/Services/AIUsageService.php:43-106`

**Problem:** `canMakeRequest()` doesn't use subscription metadata for limits.

**Current Flow:**
1. Gets plan name via string matching
2. Looks up hardcoded limits
3. Checks usage against hardcoded limit

**Should Be:**
1. Get subscription metadata via `User::subscriptionProductMetadata()`
2. Read `ai_daily_limit` from metadata
3. Check usage against metadata limit
4. Fallback to defaults if metadata missing

---

## 5. Reporting Accuracy Issues

### 5.1 ✅ Usage Statistics Are Accurate

**Location:** `app/Services/AIUsageService.php:170-210`

**Status:** ✅ Correctly queries `ai_usage_logs` table
- Counts requests correctly
- Sums tokens correctly
- Calculates costs correctly
- Groups by feature correctly

### 5.2 ⚠️ Plan Limits Display May Be Wrong

**Location:** `app/Filament/Dashboard/Widgets/AIUsageWidget.php:35-37`

```php
$planName = $this->getUserPlanName($user);  // ❌ Uses fragile string matching
$limits = $usageService->getPlanLimits($planName);  // ❌ Gets hardcoded limits
$dailyLimit = $limits['daily_limit'];
```

**Issue:**
- Shows hardcoded limit, not actual metadata limit
- If metadata says 25 but hardcoded says 20, widget shows 20
- User sees wrong limit in dashboard

### 5.3 ⚠️ Feature Usage Breakdown May Be Incomplete

**Problem:** Since email generation uses 'general' feature name:
- Email usage appears in 'general' category
- Can't see email-specific usage stats
- Can't track email generation separately from other general AI usage

---

## 6. Admin Bypass

### 6.1 ✅ Admin Bypass Works

**Location:** `app/Services/AIUsageService.php:231-249`

**Status:** ✅ Correctly checks if user is admin
**Result:** Admins get unlimited AI requests (999999 limit)

**Note:** Uses multiple methods to check admin status:
- `is_admin` property
- `hasRole('admin')` method
- Roles relationship

---

## 7. Summary of Issues

### Critical Issues (Must Fix)

1. **Hardcoded Plan Names** - System uses 'basic' but plan is 'starter'
2. **Fragile Plan Detection** - String matching on plan slug, doesn't handle 'starter'
3. **Hardcoded Limits** - Not reading from subscription metadata (`ai_daily_limit`)
4. **Missing Feature Names** - Email generation tracked as 'general', not 'email_generation'

### Medium Issues (Should Fix)

5. **Limit Display Mismatch** - Widget shows hardcoded limits, not metadata limits
6. **Feature Name Inconsistencies** - Feature names don't align with metadata keys

### Minor Issues (Nice to Have)

7. **Default Fallback** - Always defaults to 'basic' plan if detection fails
8. **Cache Invalidation** - Could be optimized

---

## 8. Recommended Fixes

### Fix 1: Use Subscription Metadata for Limits

**Change:** Modify `AIUsageService::canMakeRequest()` to read limits from metadata:

```php
// Instead of:
$planName = $this->getUserPlanName($user);
$limits = self::LIMITS[$planName] ?? self::LIMITS['basic'];
$dailyLimit = $limits['daily_limit'];

// Use:
$metadata = $user->subscriptionProductMetadata();
$dailyLimit = (int)($metadata['ai_daily_limit'] ?? 20); // Default to 20 if not set
```

### Fix 2: Add Feature Names to Email Methods

**Change:** Pass feature name when calling `chat()`:

```php
// In generateEmail():
$response = $this->chat([...], [
    'feature' => 'email_generation',
    'action' => 'generate',
    // ...
]);

// In improveEmail():
$response = $this->chat([...], [
    'feature' => 'email_generation',
    'action' => $instruction, // 'improve', 'shorten', 'expand', etc.
    // ...
]);
```

### Fix 3: Remove Hardcoded Plan Names

**Change:** Remove `getUserPlanName()` method and `LIMITS` constant
**Use:** Direct metadata access via `User::subscriptionProductMetadata()`

### Fix 4: Update Widget to Use Metadata

**Change:** `AIUsageWidget` should read limit from metadata, not hardcoded values

---

## 9. Testing Checklist

After fixes, verify:

- [ ] Starter plan users get correct daily limit from metadata
- [ ] Pro plan users get correct daily limit from metadata
- [ ] Premium plan users get correct daily limit from metadata
- [ ] Email generation is tracked as 'email_generation' feature
- [ ] Email improvement actions are tracked correctly
- [ ] Usage widget shows correct limits from metadata
- [ ] Analytics page shows correct plan limits
- [ ] Admin bypass still works
- [ ] Users without subscription get default limits (or blocked)
- [ ] Feature-specific usage tracking works correctly

---

## 10. Conclusion

The AI usage tracking system **works** but has **architectural issues**:

1. ✅ **Tracking works** - Usage is logged correctly
2. ✅ **Reporting works** - Statistics are accurate
3. ❌ **Plan integration broken** - Uses hardcoded limits instead of metadata
4. ❌ **Feature tracking incomplete** - Email features not properly categorized

**Priority:** Fix the metadata integration to align with the simple feature system we implemented. This will ensure consistency across the entire application.

