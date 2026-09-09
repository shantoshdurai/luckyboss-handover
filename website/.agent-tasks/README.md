# Lucky Boss Portal — Agent Task Execution Guide

## 🎯 Overview

This directory contains detailed instruction files for agents to execute the complete overhaul of the Lucky Boss Portal. Each file is self-contained and can be given to an AI agent to execute.

## 📋 Execution Order

**IMPORTANT**: Tasks have dependencies. Execute in this order:

```
01-DESIGN-SYSTEM.md          ← FIRST (foundation for everything)
        ↓
02-COMPONENT-LIBRARY.md      ← Second (depends on CSS from 01)
        ↓
03-LAYOUTS.md                ← Third (depends on components from 02)
04-BACKEND-HARDENING.md      ← Can run in PARALLEL with 03
        ↓
05-HOMEPAGE-AND-AUTH-PAGES.md ← Last (depends on 01, 02, 03, 04)
```

## ⚡ Parallel Execution

You can run these in parallel:
- **Group A**: 01 → 02 → 03 → 05 (frontend pipeline)
- **Group B**: 04 (backend — independent of frontend)

## 📁 Task Descriptions

| # | File | Lines | What It Does |
|---|------|-------|-------------|
| 01 | `01-DESIGN-SYSTEM.md` | ~400 | Sets up CSS, fonts (Inter + Plus Jakarta Sans), Tailwind theme, Alpine.js, brand colors |
| 02 | `02-COMPONENT-LIBRARY.md` | ~550 | Creates 15 reusable Blade components (Button, Card, Badge, Input, Modal, etc.) |
| 03 | `03-LAYOUTS.md` | ~600 | Overhauls all 4 layouts (Public, Admin, Employer, Seeker) + Header + Footer |
| 04 | `04-BACKEND-HARDENING.md` | ~400 | Form Requests, FileUploadService, AuthController fix, caching, pagination, middleware |
| 05 | `05-HOMEPAGE-AND-AUTH-PAGES.md` | ~300 | Redesigns homepage, login, seeker registration, employer registration |

## 🔧 How to Use

1. Give the task file to an AI agent (Gemini Pro, Flash, etc.)
2. Tell the agent: "Read the file at `c:\Luckyboss\luckyboss-app\.agent-tasks\XX-TASKNAME.md` and execute all instructions in it. The project is at `c:\Luckyboss\luckyboss-app`."
3. The agent should:
   - Read the instruction file
   - Create/modify the specified files
   - Run verification commands
   - Report completion

## 🏗️ Project Info
- **Stack**: Laravel 12, Tailwind CSS 4, Alpine.js, MySQL
- **Location**: `c:\Luckyboss\luckyboss-app`
- **Brand**: Navy (#031f49) + Green (#18a66a) + Blue (#2563eb)
