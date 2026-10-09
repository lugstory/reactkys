# Modernizace a refaktoring CRM systému (React + PHP)

Tento repozitář obsahuje kompletní řešení výběrového řízení na převzetí, modernizaci a refaktoring CRM aplikace. V rámci zadání byly vyřešeny všechny stanovené úkoly (1 až 5), projekt byl převeden z Webpacku na moderní bundler **Vite** a byla zajištěna plná funkčnost lokálního prostředí (databáze MariaDB, PHP backend a Vite dev server).

K projektu je vygenerována také detailní tisková technická zpráva ve formátu PDF:
📄 **[Technická zpráva o úpravách (PDF)](./CRM_Refactoring_Report.pdf)**

---

## 📌 Přehled splněných úkolů

| # | Úkol ze zadání | Stav | Klíčové dotčené soubory |
|---|---|:---:|---|
| **1** | Předělat zobrazení kontaktů, schůzek a akcí pomocí React Routing – zrušit plovoucí vrstvy | ✅ Splněno | `App.jsx`, `contactList.jsx`, `meetList.jsx`, `workshoplist.jsx`, `firmList.jsx` |
| **2** | Oprava chyby v zasílání (při druhém uložení se nepřekreslí tabulka) | ✅ Splněno | `campaignContactsList.jsx` |
| **3** | Vytvořit univerzální komponentu tabulky se všemi funkcemi a implementovat do kódu | ✅ Splněno | `DataTable.jsx`, `firmList.jsx`, `campaignList.jsx` |
| **4** | Identifikovat nepoužívané komponenty a soubory | ✅ Splněno | `ODSTRANENE_KOMPONENTY.md`, pročištěno 12 souborů |
| **5** | Rozdělit a zpřehlednit obří monolitický soubor `requests.php` | ✅ Splněno | `v3/requests.php`, `v3/api/Router.php`, `v3/api/Services.php`, `v3/api/handlers/*` |
| **+** | Převod frontendového buildu z Webpack na **Vite** | ✅ Splněno | `vite.config.mjs`, `package.json`, `index.html` |
| **+** | Lokální běh, proxy a podpora MariaDB socketu | ✅ Splněno | `v3/conn.php`, `v3/session.php`, `UrlProvider.jsx` |

---

## 🛠️ Detailní rozbor řešení jednotlivých úkolů

### Úkol 1: React Routing namísto plovoucích vrstev
* **Původní stav:** Seznamy kontaktů, schůzek a workshopů se otevíraly jako modální překryvná okna (`floating-layer`) uvnitř komponenty `firmList.jsx`. Nebylo možné použít tlačítka prohlížeče (Zpět/Vpřed), odkazovat se na firmu přes URL ani provést obnovení stránky (F5) bez ztráty kontextu.
* **Implementované řešení:**
  * V `crmWAcv/src/App.jsx` byly definovány nové routy:
    * `/firm/:firmId/contacts` ➔ `<ContactList />`
    * `/firm/:firmId/meets` ➔ `<MeetList />`
    * `/firm/:firmId/workshops` ➔ `<WorkshopList />`
  * Plovoucí vrstvy byly kompletně odstraněny a nahrazeny samostatnými plnohodnotnými stránkami s navigačním tlačítkem zpět.
  * Do komponent byl integrován hook `useParams()` pro dynamické čtení `firmId` z URL.
  * Přidáno asynchronní načítání detailu firmy (`GET /firm/:firmId`), takže stránka funguje i při přímém zadání URL nebo po refreshnutí.
  * Z `firmList.jsx` byl odstraněn veškerý mrtvý stav (`showContacts`, `showMeets`, atd.) a nahrazen přechodem přes `navigate()`.

### Úkol 2: Oprava chyby v zasílání kampaní
* **Původní stav:** V `crmWAcv/src/components/campaignContactsList.jsx` se po prvním uložení stavu tabulka překreslila, ale po druhém uložení se data na backendu uložila, avšak tabulka zůstala nezměněná.
* **Příčina chyby:** Hook `useEffect` měl závislost na `[id, isSuccessVisible]`. Při prvním uložení se `isSuccessVisible` změnilo z `false` na `true`, což vyvolalo refetch. Hodnota však zůstala `true`. Při druhém uložení se znovu nastavilo `true` ➔ React nevyhodnotil žádnou změnu stavu a `useEffect` vůbec nespustil.
* **Implementované řešení:**
  * Funkce `fetchContacts` byla vyjmuta z `useEffect` a volá se **přímo a spolehlivě** v callbacku po úspěšném uložení dat (`response.data.msg === true`).
  * `useEffect` byl zbaven závislosti na notifikaci a spouští se pouze při změně kampaně (`[id, apiUrl]`).
  * Přidán časovač `setTimeout(() => setIsSuccessVisible(false), 3000)` pro automatické skrytí notifikace.
  * Zajištěno uchování `newStatus` před vyčištěním formuláře.

### Úkol 3: Univerzální komponenta `DataTable`
* **Vytvoření komponenty:** V `crmWAcv/src/components/DataTable.jsx` byla vyvinuta modulární tabulková komponenta, která pokrývá veškeré požadavky:
  * **Řazení sloupců (Sorting):** Vzestupné i sestupné řazení kliknutím na záhlaví sloupce s vizuálními indikátory (`▲` / `▼`).
  * **Výběr řádků (Row Selection):** Checkbox pro výběr/odznačení všech řádků, výběr jednotlivých řádků i podpora hromadného výběru přes **Shift + Click**.
  * **Přepínání zalamování textu (Wrap / No-wrap):** Tlačítko pro přepnutí kompaktního zobrazení s elipsou vs. zalamování dlouhého textu.
  * **Vlastní renderování:** Podpora pro `headerRenderer` a `cellRenderer` umožňující vkládat vlastní tlačítka, akce, badgety nebo formátovaná data.
  * **Počítadlo vybraných záznamů a stav prázdných dat:** Přehledné hlášení počtu vybraných řádků.
* **Implementace do aplikace:**
  * Integrována do hlavní tabulky firem v `firmList.jsx`.
  * Integrována do tabulky kampaní v `campaignList.jsx`.

### Úkol 4: Identifikace a vyčištění nepoužívaných souborů
* Podrobný přehled s popisem jednotlivých komponent byl sepsán do samostatného dokumentu [ODSTRANENE_KOMPONENTY.md](./ODSTRANENE_KOMPONENTY.md).
* Celkem bylo identifikováno a bezpečně odstraněno **12 souborů** rozdělených do kategorií:
  1. **Zcela neimportované komponenty:** `campaignDetail.jsx`, `columnGraph.jsx`, `combo.jsx`.
  2. **Mrtvé komponenty se zakomentovaným importem:** `CopyFirmNamesButton.jsx`, `RingChart.jsx`, `searchContact.jsx`, `gauth.jsx`, `googleAuthProvider.jsx`.
  3. **Pozůstatky ručních záloh (kopie z vývoje):** `filter - kopie.jsx`, `statsByYears - kopie.jsx`, `v3/imap - kopie.php`.
  4. **Nepoužité CSS:** `css/view.css`.

### Úkol 5: Rozdělení a refaktoring `requests.php`
* **Původní stav:** Soubor `v3/requests.php` měl 527 řádků a míchal směrování, SQL dotazy, autentizaci a formátování odpovědí dohromady.
* **Nová architektura:**
  * `v3/requests.php` byl zredukován na **čistý 36řádkový router/bootstrap**.
  * Logika byla rozdělena do objektově orientované struktury ve `v3/api/`:
    * [`Router.php`](./v3/api/Router.php) – směrování HTTP metod a akcí.
    * [`Services.php`](./v3/api/Services.php) – správa DB spojení a sdílených závislostí.
    * [`Response.php`](./v3/api/Response.php) – unifikované formátování JSON výstupů a chybových kódů.
    * [`api/handlers/GetHandler.php`](./v3/api/handlers/GetHandler.php) – zpracování všech GET dotazů.
    * [`api/handlers/PostHandler.php`](./v3/api/handlers/PostHandler.php) – vytváření nových záznamů.
    * [`api/handlers/PutHandler.php`](./v3/api/handlers/PutHandler.php) – aktualizace dat.
    * [`api/handlers/DeleteHandler.php`](./v3/api/handlers/DeleteHandler.php) – mazání záznamů.

---

## ⚡ Převod z Webpacku na Vite
Původní projekt využíval těžkopádnou konfiguraci Webpacku. Pro dramatické zrychlení vývoje byl frontend zmigrován na moderní nástroj **Vite**:
* Vytvořen konfigurační soubor `crmWAcv/vite.config.mjs` s pluginy:
  * `@vitejs/plugin-react`
  * Path aliasy (`Utils` -> `src/components/utils`)
  * Proxy pravidla pro přesměrování `/rest.php` a `/requests.php` na lokální PHP backend
* Přesunut a zjednodušen `index.html` do kořene frontendového balíčku se vstupním modulem `/src/index.jsx`.
* Odstraněny zastaralé konfigurační soubory `webpack.config.*.js`.
* Aktualizovány npm skripty:
  ```json
  "scripts": {
    "dev": "vite",
    "start": "vite",
    "build": "vite build",
    "preview": "vite preview"
  }
  ```

---

## 🚀 Lokální spuštění projektu

### 1. Prerekvizity
* Node.js 18+ a npm
* PHP 8.1+ (s rozšířeními `mysqli`, `json`, `session`)
* MariaDB / MySQL server

### 2. Příprava databáze
1. Spusťte MariaDB server.
2. Vytvořte databázi `crmskchccz`.
3. Naimportujte výchozí data ze souboru `crmskchccz.sql`:
   ```bash
   mysql -u root -p crmskchccz < crmskchccz.sql
   ```
*(V souboru `v3/conn.php` je připravena automatická detekce lokální databáze včetně uživatelského socketu).*

### 3. Spuštění PHP Backend
Ve složce `v3/` spusťte vestavěný PHP server:
```bash
cd v3
php -S 127.0.0.1:8000
```

### 4. Spuštění React Frontend (Vite)
Ve složce `crmWAcv/` nainstalujte balíčky a spusťte vývojový server:
```bash
cd crmWAcv
npm install
npm run dev
```
Aplikace se spustí na `http://localhost:3000` (případně `http://localhost:5173`) a automaticky proxyuje API požadavky na běžící PHP backend na portu 8000.

### 5. Přihlašovací údaje ze zadání
* **Uživatel:** `reader`
* **Heslo:** `1234OLe`