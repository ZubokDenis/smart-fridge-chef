---
name: culinary-ui-design
description: Design principles and guidelines for creating human-crafted, editorial, and appetizing culinary and meal-planning interfaces (anti-AI aesthetic, warm organic palette, magazine typography, tactile pantry and recipe cards).
---

# Culinary Editorial & Anti-AI Design System

## Core Design Philosophy
Food and cooking are fundamentally physical, sensory, and human experiences. A culinary application should never look like an AWS cloud console, an AI generator toy, or a cold corporate dashboard. It should look and feel like an editorial publication (NYT Cooking, Kitchen Stories, Kinfolk, Epicurious) combined with the fluid tactile ergonomics of modern iOS/macOS apps.

---

## 1. Palette: Warm, Appetizing & Natural

| Role | Color | Hex | Description |
|---|---|---|---|
| **Background (Canvas)** | Warm Linen / Buttermilk | `#FBF9F5` | Natural warm paper tone, zero cold blue/grey |
| **Surface (Cards)** | Crisp Milk White | `#FFFFFF` | Elevated cards with warm subtle shadow |
| **Surface Subtle** | Soft Oat / Alabaster | `#F4F0E8` | Secondary containers, inputs, tab bars |
| **Borders** | Warm Stone / Hazelnut | `#E8E1D5` | Soft organic borders, never harsh `#ccc` |
| **Text Primary** | Dark Roast Espresso | `#1F1A16` | Deep organic black with warm undertone |
| **Text Secondary** | Warm Cocoa / Taupe | `#6B6156` | Soft readable descriptive text |
| **Text Muted** | Hazelnut Dust | `#94897E` | Minor timestamps, placeholders |
| **Primary Accent** | Rosemary / Forest Sage | `#2D5E3B` | Fresh herb green, culinary signature |
| **Primary Hover** | Deep Rosemary | `#21492E` | Interactive hover state |
| **Secondary Accent** | Terracotta / Saffron | `#C85A32` | Warm spice, highlights "нужно докупить" |
| **Success / In Stock** | Fresh Basil | `#2E7D32` | "Все продукты в наличии" |
| **Warning / Shopping** | Warm Apricot / Amber | `#D97706` | "С докупкой ингредиентов" |

---

## 2. Typography Hierarchy

1. **Editorial Headlines:** `Lora` (or `Playfair Display`), serif with warm optical sizing.
   - Use `font-style: italic` for taglines, subheadings, and culinary flourishes (*«Умный подбор блюд из того, что есть в холодильнике»*).
2. **Interactive UI & Badges:** `Montserrat` (500, 600, 700), with subtle tracking for buttons, categories, and counters.
3. **Body & Ingredients:** `Roboto` / `Plus Jakarta Sans`, clean, comfortable reading line-height (1.6).

---

## 3. Anti-AI Aesthetic Principles

- **NO Robot / AI Gizmos:** Eliminate `smart_toy`, glowing status pills with "AI connecting...", and "Сгенерировано ИИ" labels. The technology should be invisible — the user sees a culinary assistant, a smart pantry, and expert recipe matching.
- **NO Generic ERP Tables:** Never present fresh food in a spreadsheet `<table>` with cold column headers. Group ingredients by culinary category (Овощи и зелень, Молочные продукты, Мясо и птица, Бакалея) with tactile tags/capsules.
- **Appetizing Recipe Cards:** 
  - Aspect ratio 16:10 or 4:3 with smooth image zoom on hover.
  - Floating pill on photo: *"Все продукты есть"* (Sage green) or *"Докупить 2 продукта"* (Terracotta).
  - Time badge, calorie badge, difficulty tag.
- **Tactile Inputs:**
  - Dropzone styled as an appetizing culinary photo frame with warm dashed border, camera icon, and clear instructions.
  - Clean pill tabs without numbered steps ("1. Холодильник, 2. Рецепты..." $\rightarrow$ "Мой холодильник", "Рецепты", "План на неделю", "Список покупок", "Магазины").
