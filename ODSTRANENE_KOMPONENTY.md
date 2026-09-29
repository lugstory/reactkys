# Úkol 4: Identifikace a odstranění nepoužívaných komponent (souborů)

Tento dokument slouží jako přehled a záznam o identifikovaných a odstraněných nepoužívaných komponentách a souborech v projektu.

---

## 📋 Přehled odstraněných souborů

| # | Soubor / Cesta | Typ | Důvod odstranění |
|---|----------------|-----|------------------|
| 1 | `crmWAcv/src/components/campaignDetail.jsx` | React komponenta | Nikde se neimportuje, není zařazena v routách v `App.jsx`. |
| 2 | `crmWAcv/src/components/columnGraph.jsx` | React komponenta | Sloupcový graf (Recharts), nikde v projektu se nepoužívá ani neimportuje. |
| 3 | `crmWAcv/src/components/combo.jsx` | React komponenta | Generický select načítající data z URL, v celém projektu nepoužitý. |
| 4 | `crmWAcv/src/components/CopyFirmNamesButton.jsx` | React komponenta | Tlačítko pro kopírování názvů firem do schránky; bylo pouze zakomentované v `statsByYears.jsx`. |
| 5 | `crmWAcv/src/components/RingChart.jsx` | React komponenta | Prstencový graf; byl pouze zakomentovaný v `chartComponent.jsx`. |
| 6 | `crmWAcv/src/components/searchContact.jsx` | React komponenta | Formulář pro vyhledávání kontaktů; byl pouze zakomentovaný v `firmList.jsx`. |
| 7 | `crmWAcv/src/components/filter - kopie.jsx` | Duplicitní soubor | Záložní/odložená kopie souboru `filter.jsx`. |
| 8 | `crmWAcv/src/components/statsByYears - kopie.jsx` | Duplicitní soubor | Přesná 1:1 kopie souboru `statsByYears.jsx`. |
| 9 | `crmWAcv/src/components/google/gauth.jsx` | React kontext/poskytovatel | Stará Google autentizace, nikde se neimportuje. |
| 10 | `crmWAcv/src/components/google/googleAuthProvider.jsx` | React komponenta | Byla pouze zakomentována v `App.jsx`, obsahovala nefunkční importy (`./syncContacts`, cyklický import sebe sama). |
| 11 | `crmWAcv/src/css/view.css` | CSS styl | Třída `.view`, soubor se nenačítá v `index.css` ani nikde jinde. |
| 12 | `v3/imap - kopie.php` | Backend PHP skript | Zbytková záložní kopie souboru `imap.php`. |

---

## 🔍 Detailní popis identifikovaných komponent

### 1. `campaignDetail.jsx`
- **Umístění:** `crmWAcv/src/components/campaignDetail.jsx`
- **Účel:** Komponenta měla zobrazovat detail kampaně na základě ID z URL (`/api/campaigns/${id}`).
- **Stav:** V `App.jsx` pro ni neexistuje žádná `Route` (pro kampaně existují pouze cesty `/campaign`, `/campaignAdd/:id`, `/getCampaignContacts/:id` a `/campaignAdd`). Žádná jiná komponenta ji neimportuje.

### 2. `columnGraph.jsx`
- **Umístění:** `crmWAcv/src/components/columnGraph.jsx`
- **Účel:** Samostatná komponenta obalující `BarChart` z knihovny `recharts`.
- **Stav:** V projektu se grafy řeší primárně přes `Chart.js` (`react-chartjs-2`) v `chartComponent.jsx` a `companyChart.jsx`. Tato komponenta nebyla nikde importována.

### 3. `combo.jsx`
- **Umístění:** `crmWAcv/src/components/combo.jsx`
- **Účel:** Formulářový prvek `<select>`, který při načtení prováděl GET požadavek přes axios na zadanou URL adresu a naplnil položky `<option>`.
- **Stav:** Všechny formuláře v projektu používají buď nativní selecty, nebo specifické komponenty (např. `multiselect.jsx`, `selectSchoolYear.jsx`). Komponenta `combo.jsx` nebyla nikde použita.

### 4. `CopyFirmNamesButton.jsx`
- **Umístění:** `crmWAcv/src/components/CopyFirmNamesButton.jsx`
- **Účel:** Tlačítko, které mělo zkopírovat seznam názvů firem do schránky (clipboardu).
- **Stav:** V `statsByYears.jsx` byl import pouze zakomentovaný (`// import CopyFirmNamesButton from './CopyFirmNamesButton';`). Tlačítko se v JSX nevykreslovalo.

### 5. `RingChart.jsx`
- **Umístění:** `crmWAcv/src/components/RingChart.jsx`
- **Účel:** Vykreslení kruhového/prstencového grafu.
- **Stav:** V `chartComponent.jsx` byl import zakomentován (`// import RingChart from './RingChart';`). V aplikaci se nepoužíval.

### 6. `searchContact.jsx`
- **Umístění:** `crmWAcv/src/components/searchContact.jsx`
- **Účel:** Komponenta formuláře pro vyhledávání kontaktů (`SearchContactForm`).
- **Stav:** V `firmList.jsx` byl import zakomentován (`// import SearchContact from './searchContact';`). V aplikaci nebyla aktivní.

### 7. `filter - kopie.jsx` a `statsByYears - kopie.jsx`
- **Umístění:** `crmWAcv/src/components/filter - kopie.jsx`, `crmWAcv/src/components/statsByYears - kopie.jsx`
- **Účel:** Pozůstatky ručního zálohování souborů v průběhu vývoje.
- **Stav:** Aplikace importuje ostré verze `filter.jsx` a `statsByYears.jsx`. Tyto soubory s příponou `- kopie` byly mrtvým kódem.

### 8. `google/gauth.jsx` a `google/googleAuthProvider.jsx`
- **Umístění:** `crmWAcv/src/components/google/`
- **Účel:** Pokus o implementaci přihlášení přes Google OAuth a synchronizaci kontaktů.
- **Stav:** V `App.jsx` byl import zakomentován (`// import GAuthProvider from './components/google/googleAuthProvider';`). Soubor `googleAuthProvider.jsx` navíc obsahoval neexistující import `./syncContacts` a cyklický import sebe sama. Komponenta `google/AddEventToGoogleCalendar.jsx` v projektu **zůstává**, protože je aktivně používána v `eventsList.jsx`.

### 9. `css/view.css`
- **Umístění:** `crmWAcv/src/css/view.css`
- **Účel:** Styl pro třídu `.view`.
- **Stav:** Soubor nebyl importován v hlavním `index.css` a třída `.view` se v žádné komponentě nepoužívá.

### 10. `v3/imap - kopie.php`
- **Umístění:** `v3/imap - kopie.php`
- **Účel:** Duplicitní kopie backendového skriptu pro IMAP.
- **Stav:** Mrtvá záloha, ostrý skript je `v3/imap.php`.

---

## ✅ Ověření funkčnosti
Všechny aktivně používané komponenty vycházející ze vstupního bodu `index.jsx` a routeru `App.jsx` zůstaly zachovány a nedošlo k žádnému narušení funkčnosti ani závislostí aplikace.
