# Testing Plan Limits - Simple Guide

## ✅ Quick Test Command

Run this to test if limits work:

```bash
php artisan plan:test-limits
```

This will:
- Show what limits apply to your users
- Show current counts vs limits
- Verify admin bypass works
- Check metadata is being read correctly

## 🧪 Manual Testing Steps

### Test 1: Starter Plan Limits

1. **Create/Login as Starter Plan User**
   - Go to your dashboard
   - Create contacts until you reach 500
   - Try to create the 501st contact
   - ✅ **Expected**: "Create" button should be hidden/disabled

2. **Test Different Limits**
   - Contacts: 500 limit
   - Deals: 25 limit
   - Tasks: 100 limit
   - Appointments: 50 limit

### Test 2: Pro Plan (Unlimited)

1. **Login as Pro Plan User**
   - Should be able to create unlimited contacts, deals, tasks, appointments
   - ✅ **Expected**: No limits blocking creation

### Test 3: Feature Flags

1. **Goals Feature** (Pro+ only)
   - Login as Starter user
   - ✅ **Expected**: "Goals" should NOT appear in navigation
   - Login as Pro user
   - ✅ **Expected**: "Goals" should appear in navigation

2. **Contact Segments** (Pro+ only)
   - Same test as Goals

### Test 4: Admin Bypass

1. **Login as Admin**
   - Should be able to create unlimited everything
   - Should see all features
   - ✅ **Expected**: No limits apply

## 🔍 Verify Metadata is Working

Run this command to check metadata:

```bash
php artisan subscription:verify
```

This shows:
- Which products have metadata
- What limits are set
- If default product is configured

## 🐛 Troubleshooting

### If limits don't work:

1. **Check metadata exists:**
   ```bash
   php artisan subscription:verify
   ```
   - Make sure products have metadata
   - Make sure default product is set

2. **Check user has subscription:**
   ```bash
   php artisan plan:test-limits --user=USER_ID
   ```
   - Replace USER_ID with actual user ID
   - See what metadata they're getting

3. **Verify Resource methods:**
   - Check that `canCreate()` method exists in resources
   - Check that it calls `auth()->user()->canCreate()`

### If "Create" button still shows:

1. Clear cache:
   ```bash
   php artisan optimize:clear
   ```

2. Check browser cache (hard refresh: Ctrl+Shift+R)

3. Verify the Resource has `canCreate()` method

## 📊 What the Test Command Shows

```
👤 Testing User: John Doe (ID: 1)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ✅ Metadata found: 80 keys
   📊 Current Limits:
      ✅ Contacts: 450 / 500 - Can Create
      ✅ Deals: 20 / 25 - Can Create
      ❌ Tasks: 100 / 100 - BLOCKED
      ✅ Appointments: 30 / 50 - Can Create
   🎯 Feature Flags:
      ❌ Goals: Disabled (metadata: false)
      ✅ Contact Segments: Enabled (metadata: true)
```

## ✅ Success Criteria

Everything works correctly if:
- ✅ Starter users hit limits at correct numbers
- ✅ Pro users have unlimited access
- ✅ Premium users have all features
- ✅ Admins bypass all limits
- ✅ Feature flags hide/show resources correctly
- ✅ Test command shows correct limits

## 🔄 To Revert

If something breaks, just remove the `canCreate()` and `canViewAny()` methods from resources. The User model methods are safe to keep.


