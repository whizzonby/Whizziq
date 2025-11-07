# SAP & Oracle Financials - Exact Credentials Required

## 🔍 Current Implementation Status

**Finding:** SAP and Oracle are **NOT implemented** with actual import services yet.

**Current Behavior:**
- ✅ Show in UI with "Enterprise" badge
- ✅ Button shows "Contact Sales"
- ❌ No `SAPImportService.php` exists
- ❌ No `OracleImportService.php` exists
- ❌ Not in the `match()` statement at line 199-204 of `ManageFinance.php`

**This means:** Currently handled as **lead generation** for enterprise sales, not actual integration.

---

## 📋 If You Want to Implement SAP Integration

### Required Credentials for .env

#### Option 1: SAP OData (Gateway) - Most Common

```env
# ============================================================================
# SAP INTEGRATION - OData Gateway
# ============================================================================
# SAP Gateway provides RESTful OData services for accessing SAP data
# Get these from your SAP Basis team or SAP Gateway administrator
# ============================================================================

# SAP System Connection
SAP_ENABLED=false  # Set to true when ready
SAP_GATEWAY_URL="https://sap-gateway.company.com:8443/sap/opu/odata/sap"
SAP_GATEWAY_PORT=8443

# SAP Authentication (Basic Auth for OData)
SAP_USERNAME="INTEGRATION_USER"  # Technical user with read access
SAP_PASSWORD="encrypted_password"  # Strong password
SAP_CLIENT="100"  # SAP Client/Mandant (e.g., 100, 200, 300)
SAP_LANGUAGE="EN"  # EN, DE, FR, etc.

# SAP System Details
SAP_SYSTEM_ID="PRD"  # System ID (DEV, QAS, PRD)
SAP_SYSTEM_NUMBER="00"  # Instance number
SAP_COMPANY_CODE="1000"  # Your company code in SAP
SAP_CONTROLLING_AREA="1000"  # Controlling area
SAP_FISCAL_YEAR="2025"
SAP_FISCAL_YEAR_VARIANT="K4"  # Calendar year variant

# SAP OData Service Endpoints
SAP_GL_SERVICE="FGLCLEARINGBALANCE_SRV"  # General Ledger
SAP_COSTCENTER_SERVICE="FCO_PI_COSTCENTER_SRV"  # Cost Centers
SAP_PROFITCENTER_SERVICE="FCO_PI_PROFITCENTER_SRV"  # Profit Centers
SAP_VENDOR_SERVICE="MD_SUPPLIER_MASTER_SRV"  # Vendor Master
SAP_CUSTOMER_SERVICE="MD_CUSTOMER_MASTER_SRV"  # Customer Master

# Data Import Settings
SAP_IMPORT_DAYS=90  # Days of historical data to import
SAP_SYNC_FREQUENCY=1440  # Minutes (1440 = daily)
SAP_BATCH_SIZE=1000  # Records per API call
```

#### Option 2: SAP Cloud Platform (S/4HANA Cloud)

```env
# ============================================================================
# SAP S/4HANA CLOUD - OAuth 2.0
# ============================================================================
# For SAP Cloud customers using S/4HANA Cloud
# Get credentials from SAP BTP (Business Technology Platform)
# ============================================================================

SAP_CLOUD_ENABLED=false
SAP_CLOUD_BASE_URL="https://myXXXXXX.s4hana.ondemand.com"
SAP_CLOUD_API_PATH="/sap/opu/odata4/sap"

# OAuth 2.0 Credentials
SAP_CLOUD_OAUTH_CLIENT_ID="sb-your-app-name!tXXXX"
SAP_CLOUD_OAUTH_CLIENT_SECRET="your_client_secret_here"
SAP_CLOUD_OAUTH_TOKEN_URL="https://myXXXXXX.authentication.eu10.hana.ondemand.com/oauth/token"
SAP_CLOUD_OAUTH_SCOPE="API_FINANCIALPLANDATA_0001"

# Tenant Information
SAP_CLOUD_TENANT_ID="your-tenant-id"
SAP_CLOUD_LANDSCAPE="eu10"  # us10, eu10, ap21, etc.
```

#### Option 3: SAP Business One (SMB Version)

```env
# ============================================================================
# SAP BUSINESS ONE - Service Layer API
# ============================================================================
# For SAP Business One customers (SMB version of SAP)
# Get from SAP Business One Service Layer administrator
# ============================================================================

SAP_B1_ENABLED=false
SAP_B1_BASE_URL="https://sap-b1-server:50000/b1s/v1"
SAP_B1_COMPANY_DB="SBODEMOUS"  # Company database name
SAP_B1_USERNAME="manager"
SAP_B1_PASSWORD="encrypted_password"
SAP_B1_LANGUAGE="en-US"
```

### Where to Get SAP Credentials

1. **From Your SAP Basis Team:**
   - Ask for "Technical User for API Integration"
   - Needs authorization objects: `S_RFC`, `S_TABU_DIS`, `S_DEVELOP`
   - Read-only access to tables: `BKPF`, `BSEG`, `CSKS`, `CEPC`, `KNA1`, `LFA1`

2. **From SAP Gateway Admin:**
   - Request OData service activation
   - Get Gateway URL and port
   - Test access with Postman first

3. **From SAP Cloud Platform:**
   - BTP Cockpit > Instances and Subscriptions
   - Create Service Instance
   - Generate Service Key (contains credentials)

### SAP Authorization Requirements

**Minimum SAP Authorizations Needed:**

```
S_TABU_DIS - Table Display Authorization
  - DICBERCLS: *
  - ACTVT: 03 (Display)

S_TABU_NAM - Table Maintenance by Name
  - ACTVT: 03 (Display)
  - TABLE: BKPF, BSEG, SKA1, CSKS, CEPC, KNA1, LFA1

S_RFC - Authorization for RFC Access
  - RFC_TYPE: FUGR (Function Group)
  - RFC_NAME: *
  - ACTVT: 16 (Execute)

S_SERVICE - Web Service Authorization
  - SRV_NAME: *
  - ACTVT: 03 (Display)
```

---

## 📋 If You Want to Implement Oracle Integration

### Required Credentials for .env

#### Option 1: Oracle Fusion Cloud (Most Modern)

```env
# ============================================================================
# ORACLE FUSION CLOUD - REST API
# ============================================================================
# Oracle Cloud ERP (SaaS version)
# Get from Oracle Cloud Console
# ============================================================================

ORACLE_ENABLED=false
ORACLE_CLOUD_BASE_URL="https://XXXX.fa.us2.oraclecloud.com"  # Your pod URL
ORACLE_CLOUD_USERNAME="integration.user@company.com"
ORACLE_CLOUD_PASSWORD="encrypted_password"

# OAuth 2.0 (Recommended for Fusion)
ORACLE_CLOUD_CLIENT_ID="your_client_id_here"
ORACLE_CLOUD_CLIENT_SECRET="your_client_secret_here"
ORACLE_CLOUD_TOKEN_URL="https://login.us2.oraclecloud.com/oam/oauth2/tokens"
ORACLE_CLOUD_SCOPE="urn:opc:resource:fa:instanceid=XXXX"

# Business Unit Configuration
ORACLE_BUSINESS_UNIT_NAME="US Operations"
ORACLE_BUSINESS_UNIT_ID="300000001234567"
ORACLE_LEDGER_NAME="US Primary Ledger"
ORACLE_LEDGER_ID="1"
ORACLE_LEGAL_ENTITY_ID="300000001234568"

# Chart of Accounts
ORACLE_CHART_OF_ACCOUNTS_ID="1"
ORACLE_CALENDAR_NAME="Month"
ORACLE_CURRENCY="USD"

# REST API Endpoints
ORACLE_API_VERSION="23.09.73.00"  # Check your version
ORACLE_GL_ENDPOINT="/fscmRestApi/resources/${ORACLE_API_VERSION}/generalLedgerBalances"
ORACLE_AP_ENDPOINT="/fscmRestApi/resources/${ORACLE_API_VERSION}/invoices"
ORACLE_AR_ENDPOINT="/fscmRestApi/resources/${ORACLE_API_VERSION}/receivablesInvoices"
```

#### Option 2: Oracle EBS (E-Business Suite) - On-Premise

```env
# ============================================================================
# ORACLE E-BUSINESS SUITE - SOAP/REST APIs
# ============================================================================
# Oracle EBS (On-premise version)
# Get from your Oracle EBS system administrator
# ============================================================================

ORACLE_EBS_ENABLED=false
ORACLE_EBS_BASE_URL="https://ebs.company.com:8443"
ORACLE_EBS_USERNAME="APPS"  # Or integration user
ORACLE_EBS_PASSWORD="encrypted_password"

# EBS Connection Details
ORACLE_EBS_RESPONSIBILITY_KEY="GL_SUPER_USER"
ORACLE_EBS_RESPONSIBILITY_ID="20420"
ORACLE_EBS_RESPONSIBILITY_APPLICATION_ID="101"
ORACLE_EBS_SECURITY_GROUP_KEY="STANDARD"
ORACLE_EBS_ORG_ID="101"  # Operating Unit

# Database Connection (Alternative)
ORACLE_EBS_DB_HOST="ebs-db.company.com"
ORACLE_EBS_DB_PORT=1521
ORACLE_EBS_DB_SERVICE="EBSPRD"
ORACLE_EBS_DB_USERNAME="APPS_READ"
ORACLE_EBS_DB_PASSWORD="encrypted_password"

# EBS-Specific Settings
ORACLE_EBS_SET_OF_BOOKS_ID="1"
ORACLE_EBS_COA_ID="101"  # Chart of Accounts ID
ORACLE_EBS_PERIOD_NAME="JAN-25"  # GL Period
```

#### Option 3: Oracle NetSuite (Cloud ERP for SMB)

```env
# ============================================================================
# ORACLE NETSUITE - SuiteScript/RESTlet
# ============================================================================
# NetSuite (Cloud ERP, now owned by Oracle)
# Get from NetSuite > Setup > Integration > Web Services Preferences
# ============================================================================

NETSUITE_ENABLED=false
NETSUITE_ACCOUNT_ID="1234567"  # Your NetSuite account ID
NETSUITE_REALM="1234567_SB1"  # Format: AccountID_Environment

# Token-Based Authentication (TBA) - Recommended
NETSUITE_CONSUMER_KEY="your_consumer_key"
NETSUITE_CONSUMER_SECRET="your_consumer_secret"
NETSUITE_TOKEN_ID="your_token_id"
NETSUITE_TOKEN_SECRET="your_token_secret"

# REST API Settings
NETSUITE_REST_URL="https://1234567.restlets.api.netsuite.com/app/site/hosting/restlet.nl"
NETSUITE_RESTLET_SCRIPT_ID="customscript_financial_integration"
NETSUITE_RESTLET_DEPLOYMENT_ID="customdeploy_financial_integration"

# SuiteTalk (SOAP) Alternative
NETSUITE_WSDL_URL="https://1234567.suitetalk.api.netsuite.com/wsdl/v2024_1_0/netsuite.wsdl"
NETSUITE_SOAP_URL="https://1234567.suitetalk.api.netsuite.com/services/NetSuitePort_2024_1"
```

### Where to Get Oracle Credentials

1. **Oracle Fusion Cloud:**
   - Login to Oracle Cloud Console
   - Identity & Security > Users
   - Create integration user with "Financial Analyst" role
   - Or use OAuth: Cloud Console > API Keys

2. **Oracle EBS:**
   - Ask your DBA/System Administrator
   - Need APPS password or integration user
   - Request read-only responsibility
   - Get from: System Administrator > Security > User > Define

3. **Oracle NetSuite:**
   - Setup > Company > Enable Features > SuiteCloud
   - Setup > Integration > Manage Integrations
   - Create new integration
   - Generate token credentials

### Oracle Authorization Requirements

**Minimum Oracle Fusion Roles:**

```
- Financial Analyst
- General Accounting Manager (View Only)
- Payables Manager (View Only)
- Receivables Manager (View Only)
```

**Or create custom role with privileges:**
```
- View General Ledger Balances
- View Journal Entries
- View Invoices (AP)
- View Transactions (AR)
- View Chart of Accounts
- View Suppliers
- View Customers
```

---

## 🔐 Security Best Practices

### For SAP:

1. **Create Dedicated Technical User:**
   ```
   Username: WHIZIQ_INTEGRATION
   Type: System User (not Dialog)
   Validity: 365 days
   Permissions: Read-only
   ```

2. **Use Certificate-Based Auth (Production):**
   ```env
   SAP_AUTH_TYPE="certificate"
   SAP_CLIENT_CERT_PATH="/path/to/client-cert.pem"
   SAP_CLIENT_KEY_PATH="/path/to/client-key.pem"
   SAP_CA_CERT_PATH="/path/to/ca-cert.pem"
   ```

3. **IP Whitelisting:**
   - Add your app's IP to SAP Gateway allowed list
   - Configure in transaction `SICF`

### For Oracle:

1. **Create Integration User:**
   ```sql
   -- Oracle EBS
   CREATE USER WHIZIQ_INTEGRATION IDENTIFIED BY strong_password;
   GRANT CONNECT TO WHIZIQ_INTEGRATION;
   GRANT SELECT ON GL_JE_HEADERS TO WHIZIQ_INTEGRATION;
   GRANT SELECT ON GL_JE_LINES TO WHIZIQ_INTEGRATION;
   -- Add more as needed
   ```

2. **Use OAuth 2.0 (Fusion Cloud):**
   - More secure than username/password
   - Tokens expire automatically
   - Can revoke access instantly

3. **Network Security:**
   - Use VPN connection if on-premise
   - Require HTTPS/TLS 1.2+
   - Implement rate limiting

---

## 📊 Data Volume Considerations

### SAP Data Volumes (Typical Enterprise):

```
General Ledger Entries: 1-10 million lines/year
Vendors: 10,000 - 100,000
Customers: 50,000 - 500,000
Cost Centers: 1,000 - 10,000
Profit Centers: 100 - 1,000
```

**API Limits:**
- OData: 1,000 records per request (use $skip and $top)
- Max response time: 30 seconds
- Rate limit: 100 requests/minute

### Oracle Data Volumes:

```
Journal Entries: 500,000 - 5 million/year
AP Invoices: 100,000 - 1 million/year
AR Invoices: 50,000 - 500,000/year
Suppliers: 5,000 - 50,000
Customers: 10,000 - 100,000
```

**API Limits (Fusion Cloud):**
- REST: 500 records per request (pagination required)
- Rate limit: 500 requests/hour per user
- Concurrent connections: 10 max

---

## 💾 Database Storage Requirements

### For SAP Integration:

```sql
-- Add to financial_connections table
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS sap_client VARCHAR(3);
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS sap_system_id VARCHAR(10);
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS sap_company_code VARCHAR(4);
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS sap_controlling_area VARCHAR(4);
```

### For Oracle Integration:

```sql
-- Add to financial_connections table
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS oracle_business_unit_id VARCHAR(50);
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS oracle_ledger_id VARCHAR(50);
ALTER TABLE financial_connections ADD COLUMN IF NOT EXISTS oracle_legal_entity_id VARCHAR(50);
```

---

## 🧪 Testing Credentials

### SAP Test System:

```env
# SAP Public Test System (Free for testing)
SAP_GATEWAY_URL="https://sapes5.sapdevcenter.com/sap/opu/odata/sap"
SAP_USERNAME="your_sap_gateway_user"  # Register at developers.sap.com
SAP_PASSWORD="your_password"
SAP_CLIENT="002"  # Public test client
```

**Get test access:** https://developers.sap.com/trials-downloads.html

### Oracle Test System:

```env
# Oracle Cloud Free Tier
ORACLE_CLOUD_BASE_URL="https://your-trial-instance.oraclecloud.com"
ORACLE_CLOUD_USERNAME="trial_user@email.com"
ORACLE_CLOUD_PASSWORD="trial_password"
```

**Get test access:** https://www.oracle.com/cloud/free/

---

## 📦 Required PHP Libraries

### For SAP:

```bash
# Option 1: Pure PHP (HTTP requests)
composer require guzzlehttp/guzzle  # Already have this

# Option 2: SAP-specific library (optional)
composer require php-sap/saprfc-koucky  # For RFC connections
```

### For Oracle:

```bash
# Option 1: Pure PHP (REST/SOAP)
composer require guzzlehttp/guzzle  # For REST
composer require php-http/guzzle7-adapter  # HTTP adapter

# Option 2: Oracle PDO (for direct DB access)
# Requires Oracle Instant Client installed on server
composer require yajra/laravel-oci8  # Laravel Oracle package
```

---

## ✅ Complete Credentials Summary

### MINIMUM Required for SAP:

```env
SAP_GATEWAY_URL="..."
SAP_USERNAME="..."
SAP_PASSWORD="..."
SAP_CLIENT="..."
SAP_COMPANY_CODE="..."
```

### MINIMUM Required for Oracle Fusion:

```env
ORACLE_CLOUD_BASE_URL="..."
ORACLE_CLOUD_USERNAME="..."
ORACLE_CLOUD_PASSWORD="..."
ORACLE_BUSINESS_UNIT_ID="..."
ORACLE_LEDGER_ID="..."
```

### MINIMUM Required for Oracle EBS:

```env
ORACLE_EBS_BASE_URL="..."
ORACLE_EBS_USERNAME="..."
ORACLE_EBS_PASSWORD="..."
ORACLE_EBS_RESPONSIBILITY_ID="..."
ORACLE_EBS_ORG_ID="..."
```

---

## 🚨 Important Notes

1. **SAP/Oracle are NOT currently implemented** - They show in UI but don't have import services
2. **Cost to implement:** $80k-$150k for both platforms
3. **Timeline:** 6-12 months for full implementation
4. **Maintenance:** $20k-$40k/year ongoing

**Recommendation:** Keep current "Enterprise" approach unless you have confirmed enterprise customers willing to pay for custom integration.

---

**Last Updated:** 2025-10-28
**Status:** ⚠️ Not implemented - shown as enterprise lead generation only
**To Implement:** Would need to create `SAPImportService.php` and `OracleImportService.php` with above credentials
