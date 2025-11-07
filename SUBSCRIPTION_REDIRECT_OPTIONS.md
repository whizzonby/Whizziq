# Options for Handling Subscription Requirements

## Current Situation

- Users without subscription try to create contacts
- `canCreate()` returns `false`
- Filament shows **403 Forbidden** (not user-friendly)

## Goal

Instead of 403, redirect users to subscription page with a friendly message.

---

## Option 1: Use `mount()` in Create Page (RECOMMENDED - Simple)

**How it works:**
- Override `mount()` method in CreateContact page
- Check if user can create
- If not, show notification and redirect

**Implementation:**
```php
// In CreateContact.php
public function mount(): void
{
    if (!ContactResource::canCreate()) {
        Notification::make()
            ->title('Subscription Required')
            ->body('Please subscribe to a plan to create contacts.')
            ->warning()
            ->persistent()
            ->send();
        
        $this->redirect(SubscriptionResource::getUrl('index'));
    }
}
```

**Pros:**
- ✅ Simple and clean
- ✅ Shows notification before redirect
- ✅ Works for all Create pages
- ✅ No 403 error

**Cons:**
- ⚠️ Page loads briefly before redirect

**Best for:** Simple, friendly redirect with message

---

## Option 2: Use `canAccess()` with Authorization Exception (Filament Native)

**How it works:**
- Throw `AuthorizationException` in `canAccess()`
- Filament catches it and shows custom error page
- Customize error page to show subscription link

**Implementation:**
```php
// In CreateContact.php
public static function canAccess(array $parameters = []): bool
{
    if (!ContactResource::canCreate()) {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'You need a subscription to create contacts.'
        );
    }
    return true;
}
```

**Pros:**
- ✅ Uses Filament's built-in authorization
- ✅ Customizable error pages
- ✅ Standard Laravel pattern

**Cons:**
- ⚠️ Still shows error page (not redirect)
- ⚠️ Need to customize error page template

**Best for:** If you want custom error pages

---

## Option 3: Hide Create Button + Show Upgrade Prompt (Best UX)

**How it works:**
- Keep `canCreate()` returning false (hides create button)
- Add upgrade widget/prompt on list page
- Show message: "Subscribe to create contacts"

**Implementation:**
```php
// In ContactResource.php - already done
public static function canCreate(): bool
{
    return auth()->user()?->canCreate(Contact::class, 'crm_contacts_limit') ?? false;
}

// In ListContacts.php - add upgrade prompt
protected function getHeaderWidgets(): array
{
    if (!ContactResource::canCreate()) {
        return [
            UpgradePromptWidget::make()
                ->title('Subscribe to Create Contacts')
                ->description('Get started with a plan to create and manage contacts.')
                ->action('Subscribe', SubscriptionResource::getUrl('index'))
        ];
    }
    return [];
}
```

**Pros:**
- ✅ Best user experience
- ✅ No 403 errors
- ✅ Clear upgrade path
- ✅ Users see what they're missing

**Cons:**
- ⚠️ Need to create upgrade widget component
- ⚠️ More code

**Best for:** Best user experience, marketing-focused

---

## Option 4: Redirect in `canAccess()` Method (Not Recommended)

**How it works:**
- Try to redirect inside `canAccess()`
- Use `redirect()` helper

**Implementation:**
```php
public static function canAccess(array $parameters = []): bool
{
    if (!ContactResource::canCreate()) {
        redirect(SubscriptionResource::getUrl('index'))
            ->with('message', 'Please subscribe to create contacts.')
            ->send();
        return false;
    }
    return true;
}
```

**Pros:**
- ✅ Direct redirect

**Cons:**
- ❌ `canAccess()` is static - redirect doesn't work well
- ❌ Can cause issues with Filament's lifecycle
- ❌ Not recommended by Filament docs

**Best for:** Not recommended

---

## Option 5: Middleware Approach (Global Solution)

**How it works:**
- Create middleware that checks subscription
- Redirects before page loads
- Apply to all create pages

**Implementation:**
```php
// Create middleware: app/Http/Middleware/RequireSubscription.php
public function handle($request, Closure $next)
{
    if (auth()->check() && !auth()->user()->hasSubscription()) {
        return redirect()->route('filament.dashboard.resources.subscriptions.index')
            ->with('message', 'Please subscribe to access this feature.');
    }
    return $next($request);
}

// Apply to routes
Route::middleware(['auth', 'require.subscription'])->group(function () {
    // Protected routes
});
```

**Pros:**
- ✅ Global solution
- ✅ Works for all pages
- ✅ Centralized logic

**Cons:**
- ⚠️ More complex setup
- ⚠️ Need to apply to many routes
- ⚠️ May interfere with Filament routing

**Best for:** If you want global enforcement

---

## Option 6: Custom Authorization Gate (Laravel Pattern)

**How it works:**
- Use Laravel's Gate system
- Define gates in AuthServiceProvider
- Use in policies or checks

**Implementation:**
```php
// In AuthServiceProvider
Gate::define('create-contact', function (User $user) {
    return $user->canCreate(Contact::class, 'crm_contacts_limit');
});

// In CreateContact.php
public static function canAccess(array $parameters = []): bool
{
    if (!Gate::allows('create-contact')) {
        // Redirect logic
    }
    return true;
}
```

**Pros:**
- ✅ Standard Laravel pattern
- ✅ Reusable gates
- ✅ Centralized authorization logic

**Cons:**
- ⚠️ More setup
- ⚠️ Still need redirect logic

**Best for:** If you want centralized authorization

---

## Recommendation: Option 1 (Simple) or Option 3 (Best UX)

### **Option 1** - Quick & Simple
- Easiest to implement
- Works immediately
- Shows friendly message
- Good for MVP

### **Option 3** - Best User Experience
- No errors
- Clear upgrade path
- Marketing opportunity
- Professional feel

---

## Comparison Table

| Option | Complexity | UX | Implementation Time | Best For |
|--------|-----------|-----|---------------------|----------|
| **Option 1: mount()** | ⭐ Simple | ⭐⭐⭐ Good | 5 min | Quick fix |
| **Option 2: AuthorizationException** | ⭐⭐ Medium | ⭐⭐ OK | 15 min | Custom errors |
| **Option 3: Upgrade Widget** | ⭐⭐⭐ Complex | ⭐⭐⭐⭐⭐ Excellent | 30 min | Best UX |
| **Option 4: Redirect in canAccess** | ⭐ Simple | ⭐⭐ OK | 5 min | Not recommended |
| **Option 5: Middleware** | ⭐⭐⭐⭐ Complex | ⭐⭐⭐ Good | 60 min | Global solution |
| **Option 6: Gate** | ⭐⭐⭐ Medium | ⭐⭐⭐ Good | 30 min | Centralized |

---

## Which Option Should We Use?

**For simplicity and quick fix:** Option 1 (mount method)
- Add 5 lines of code
- Works immediately
- Shows notification
- Redirects to subscriptions

**For best user experience:** Option 3 (Upgrade Widget)
- No errors
- Clear upgrade prompts
- Better conversion
- More professional

What would you prefer?


