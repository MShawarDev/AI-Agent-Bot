# User Guide — Sales Assistant Chatbot

## Table of Contents

- [Logging In](#logging-in)
- [For Client Users](#for-client-users)
  - [Chat Interface](#chat-interface)
  - [Asking Questions](#asking-questions)
  - [Managing Conversations](#managing-conversations)
  - [Uploading Reports](#uploading-reports)
  - [Your Profile](#your-profile)
- [For Administrators](#for-administrators)
  - [Admin Area Overview](#admin-area-overview)
  - [Managing Clients](#managing-clients)
  - [Managing Users](#managing-users)
  - [Managing Reports](#managing-reports)
  - [Usage Statistics](#usage-statistics)

---

## Logging In

1. Open the app URL in your browser.
2. You are redirected to the **Login** page automatically.
3. Enter your **email address** and **password**, then click **Log in**.

> There is no public registration. Accounts are created by an administrator.

---

## For Client Users

### Chat Interface

After logging in you land on the **Chat** page.

| Area | What it does |
|---|---|
| **Header** | Shows the bot name and how many messages are in the current conversation |
| **New button** | Clears the screen and starts a fresh conversation |
| **Message area** | Scrollable history of the current conversation |
| **Input field** | Type your question here; press **Enter** or click **Send** |
| **Sign out** | Logs you out |

When you first open the page your previous conversation is loaded automatically so you can pick up where you left off.

---

### Asking Questions

The assistant has access to your business's sales reports. You can ask questions in plain English.

**Examples that work well:**

- *"What were the total sales for 19 June 2026?"*
- *"Compare revenue between last Monday and Tuesday."*
- *"Which payment method was most used this week?"*
- *"What are the top-selling service categories?"*
- *"Was VAT collected correctly on the 20th?"*

**How it works behind the scenes:**

1. The assistant first checks which report dates are available.
2. It fetches only the specific report(s) your question needs.
3. It answers using the figures from those reports.
4. Raw report files are never sent outside the server.

**Tips for better answers:**

- Include the **date** when asking about a specific day (e.g. "19 June" or "2026-06-19").
- Ask one question at a time for the clearest response.
- If the assistant says a report doesn't exist, check the **Reports** page to confirm the file was uploaded.

**Starter prompts** — if your account has them configured, clickable example questions appear on the empty chat screen. Click any to send it instantly.

---

### Managing Conversations

| Action | How |
|---|---|
| **Start a new conversation** | Click **New** in the header |
| **Reload a past conversation** | Refresh the page — the most recent conversation reloads automatically |

Each conversation is saved server-side, so refreshing or logging out and back in does not lose your history.

---

### Uploading Reports

Click **Reports** in the navigation bar.

**To upload a new report:**

1. Click **Choose File** and select your report file.
2. Accepted formats: **PDF, DOC, DOCX, XLS, XLSX**.
3. Click **Upload**.
4. The file is parsed immediately and becomes available to the assistant within seconds.

**File limits** (set by your administrator):
- Maximum file size: 10 MB per file (default)
- Maximum number of files per account: 50 (default)

**To delete a report:**

Click **Delete** next to any report in the list. This removes the report from the assistant's knowledge — it cannot answer questions about that date any more.

**Naming tip:** Files named like `DailyCloseout_6-19-2026_to_6-19-2026.pdf` are recognised automatically and labelled "19 Jun 2026". Any other filename is stored as-is.

---

### Your Profile

Click your name in the top-right navigation → **Profile** to:

- Update your display name and email address.
- Change your password.
- Delete your account.

---

## For Administrators

Administrators have access to everything client users can do, plus the **Admin** area (visible in the navigation bar when logged in as an admin).

---

### Admin Area Overview

The admin area is at `/admin`. The navigation links are:

| Link | Purpose |
|---|---|
| **Admin** | Client list — your main hub |
| **Usage** | Message and activity statistics across all clients |

---

### Managing Clients

A **client** is a tenant account. Each client has its own users, reports, bot configuration, and conversation history.

#### Create a client

1. Go to **Admin → Clients → + New Client**.
2. Fill in the fields:

| Field | Description |
|---|---|
| **Company Name** | Displayed in the admin area |
| **Slug** | URL-safe identifier, e.g. `salon-dubai` — used internally, never shown to users |
| **Bot Name** | The name displayed in the chat header (e.g. "Sales Assistant") |
| **System Prompt** | The instructions that shape the assistant's personality and rules. Leave blank to use the default sales-reporting prompt |
| **Currency Code** | Shown in answers, e.g. `AED`, `USD` |
| **Brand Color** | Hex color for the chat header icon, e.g. `#4f46e5` |
| **Starter Prompts** | One suggested question per line — appear as clickable buttons on the empty chat screen |
| **Active** | Uncheck to disable the client without deleting it |

3. Click **Create Client**.

#### Edit a client

From the client detail page, click **Edit**. All fields can be updated at any time. Changes to the system prompt take effect on the next chat message.

#### Delete a client

On the Edit page, click **Delete client**. This permanently deletes the client and all their users, reports, and conversations.

#### Client detail page

Click **View** on any client in the list to see:

- **Stats:** user count, report count, conversation count.
- **Users table:** all users belonging to this client, with quick links to edit them.
- **Reports table:** all ingested reports with their dates and filenames; you can delete any report from here.

---

### Managing Users

Users belong to a client and can only see their own client's data.

#### Create a user

1. Open a client's detail page.
2. Click **+ Add user**.
3. Fill in:

| Field | Notes |
|---|---|
| **Name** | Display name |
| **Email** | Used to log in — must be unique across the whole system |
| **Password** | Minimum 8 characters; share this with the user securely |
| **Admin** | Check to grant full admin access across all clients |

4. Click **Create User**.

Share the login URL, email, and password with the user directly. There is no self-service registration or password-reset email configured by default.

#### Edit a user

Click **Edit** next to any user on the client detail page or from the Users list. You can update their name, email, password (leave blank to keep the current one), and admin status.

#### Delete a user

On the user Edit page, click **Delete user**. This removes the account but leaves their conversations in the database (scoped to the client).

---

### Managing Reports

Admins can manage reports for any client from the **client detail page → Reports table**.

Click **Delete** next to a report to remove it. The assistant will no longer be able to answer questions about that date.

To **upload** reports on behalf of a client, either:
- Log in as one of that client's users and use the Reports page, **or**
- Use the CLI on the server: `php artisan sales:ingest --client=<slug>`

---

### Usage Statistics

Go to **Admin → Usage** for a system-wide activity overview:

| Section | What it shows |
|---|---|
| **Totals** | All-time message count split by user and assistant messages |
| **Daily chart** | Bar chart of total messages per day for the last 30 days |
| **Per-client table** | Each client's user count, report count, conversation count, and message volume (last 30 days and all-time) |

Click a client name in the table to jump to their detail page.
