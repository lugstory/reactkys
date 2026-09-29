# Úkol 4: Identifikace a odstranění nepoužívaných komponent (souborů)

Tento dokument slouží jako přehled a podrobný záznam o identifikovaných a odstraněných nepoužívaných komponentách a souborech v projektu.

---

## 📋 Přehled odstraněných souborů podle kategorií

### Kategorie A: Zcela neimportované komponenty (0 výskytů)
Komponenty, které se v celém projektu nikde neimportují ani nevyskytují:

| # | Soubor | Typ | Důvod odstranění |
|---|--------|-----|------------------|
| 1 | `crmWAcv/src/components/campaignDetail.jsx` | React komponenta | Nikde se neimportuje a v `App.jsx` pro ni neexistuje žádná routa. |
| 2 | `crmWAcv/src/components/columnGraph.jsx` | React komponenta | Sloupcový graf (Recharts), v celém projektu se nepoužívá ani neimportuje. |
| 3 | `crmWAcv/src/components/combo.jsx` | React komponenta | Generický select načítající data z URL, v celém projektu nepoužitý. |

---

### Kategorie B: „Mrtvé“ komponenty se zakomentovaným importem
Komponenty, které v kódu zůstaly pouze jako zakomentovaný řádek `// import ...`, ale v JSX se vůbec nevykreslují ani nepoužívají (povrchní textový vyhledávač by se na nich mohl nachytat, ale reálně jsou nepoužívané):

| # | Soubor | Typ | Kde byl zakomentovaný import |
|---|--------|-----|------------------------------|
| 4 | `crmWAcv/src/components/CopyFirmNamesButton.jsx` | React komponenta | `statsByYears.jsx:2` (`// import CopyFirmNamesButton from './CopyFirmNamesButton';`) |
| 5 | `crmWAcv/src/components/RingChart.jsx` | React komponenta | `chartComponent.jsx:15` (`// import RingChart from './RingChart';`) |
| 6 | `crmWAcv/src/components/searchContact.jsx` | React komponenta | `firmList.jsx:17` (`// import SearchContact from './searchContact';`) |
| 7 | `crmWAcv/src/components/google/gauth.jsx` | React kontext | Kontext pro Google OAuth, nikde se neimportuje. |
| 8 | `crmWAcv/src/components/google/googleAuthProvider.jsx` | React komponenta | `App.jsx:17` (`// import GAuthProvider...`). Navíc obsahovala neexistující import `./syncContacts` a cyklický import sebe sama. |

*(Poznámka: Komponenta `crmWAcv/src/components/google/AddEventToGoogleCalendar.jsx` v projektu **zůstala**, protože je aktivně používána v `eventsList.jsx`.)*

---

### Kategorie C: Zbytkové a duplicitní zálohy z vývoje
Soubory, které vznikly ručním kopírováním (Windows Explorer ` - kopie`):

| # | Soubor | Typ | Důvod odstranění |
|---|--------|-----|------------------|
| 9 | `crmWAcv/src/components/filter - kopie.jsx` | Duplicitní soubor | Stará záložní kopie souboru `filter.jsx`. |
| 10 | `crmWAcv/src/components/statsByYears - kopie.jsx` | Duplicitní soubor | Přesná 1:1 kopie souboru `statsByYears.jsx`. |
| 11 | `v3/imap - kopie.php` | Backend PHP | Zbytková záložní kopie skriptu `imap.php`. |

---

### Kategorie D: Nepoužité styly
| # | Soubor | Typ | Důvod odstranění |
|---|--------|-----|------------------|
| 12 | `crmWAcv/src/css/view.css` | CSS styl | Definuje třídu `.view`. Soubor se neimportuje v `index.css` a třída se v žádné komponentě nepoužívá. |

---

## 🔍 Detailní rozbor identifikovaných komponent

### 1. `campaignDetail.jsx`
- **Cesta:** `crmWAcv/src/components/campaignDetail.jsx`
- **Účel:** Měla zobrazovat detail kampaně na základě ID z URL (`/api/campaigns/${id}`).
- **Stav:** V `App.jsx` pro ni neexistuje žádná `Route` (pro kampaně existují pouze cesty `/campaign`, `/campaignAdd/:id`, `/getCampaignContacts/:id` a `/campaignAdd`). Žádná jiná komponenta ji neimportuje.

### 2. `columnGraph.jsx`
- **Cesta:** `crmWAcv/src/components/columnGraph.jsx`
- **Účel:** Samostatná komponenta obalující `BarChart` z knihovny `recharts`.
- **Stav:** V projektu se grafy řeší primárně přes `Chart.js` (`react-chartjs-2`) v `chartComponent.jsx` a `companyChart.jsx`. Tato komponenta nebyla nikde importována.

### 3. `combo.jsx`
- **Cesta:** `crmWAcv/src/components/combo.jsx`
- **Účel:** Formulářový prvek `<select>`, který při načtení prováděl GET požadavek přes axios na zadanou URL adresu a naplnil položky `<option>`.
- **Stav:** Všechny formuláře v projektu používají buď nativní selecty, nebo specifické komponenty (např. `multiselect.jsx`, `selectSchoolYear.jsx`). Komponenta `combo.jsx` nebyla nikde použita.

### 4. `CopyFirmNamesButton.jsx`
- **Cesta:** `crmWAcv/src/components/CopyFirmNamesButton.jsx`
- **Účel:** Tlačítko pro zkopírování seznamu firem do schránky (clipboardu).
- **Stav:** V `statsByYears.jsx` byl import pouze zakomentovaný. V JSX se tlačítko nevykreslovalo.

### 5. `RingChart.jsx`
- **Cesta:** `crmWAcv/src/components/RingChart.jsx`
- **Účel:** Vykreslení kruhového/prstencového grafu.
- **Stav:** V `chartComponent.jsx` byl import zakomentován. V aplikaci se nepoužíval.

### 6. `searchContact.jsx`
- **Cesta:** `crmWAcv/src/components/searchContact.jsx`
- **Účel:** Formulář pro vyhledávání kontaktů (`SearchContactForm`).
- **Stav:** V `firmList.jsx` byl import zakomentován. V aplikaci nebyla komponenta aktivní.

### 7. `google/gauth.jsx` a `google/googleAuthProvider.jsx`
- **Cesta:** `crmWAcv/src/components/google/`
- **Účel:** Pokus o Google OAuth přihlášení a synchronizaci kontaktů.
- **Stav:** V `App.jsx` byl import zakomentován. Soubor `googleAuthProvider.jsx` navíc obsahoval neexistující import `./syncContacts` a cyklický import sebe sama. Komponenta `google/AddEventToGoogleCalendar.jsx` v projektu **zůstává**, protože je aktivně používána v `eventsList.jsx`.

### 8. `filter - kopie.jsx` a `statsByYears - kopie.jsx`
- **Cesta:** `crmWAcv/src/components/`
- **Účel:** Pozůstatky ručního zálohování souborů v průběhu vývoje.
- **Stav:** Aplikace importuje ostré verze `filter.jsx` a `statsByYears.jsx`. Soubory s příponou `- kopie` byly mrtvým kódem.

### 9. `css/view.css`
- **Cesta:** `crmWAcv/src/css/view.css`
- **Účel:** Styl pro třídu `.view`.
- **Stav:** Soubor nebyl importován v hlavním `index.css` a třída `.view` se v žádné komponentě nepoužívá.

### 10. `v3/imap - kopie.php`
- **Cesta:** `v3/imap - kopie.php`
- **Účel:** Duplicitní kopie backendového skriptu pro IMAP.
- **Stav:** Mrtvá záloha, ostrý skript je `v3/imap.php`.

---

## ✅ Ověření stromu závislostí
Všechny aktivně používané komponenty vycházející ze vstupního bodu `index.jsx` a routeru `App.jsx` zůstaly zachovány a nedošlo k žádnému narušení funkčnosti ani závislostí aplikace.
