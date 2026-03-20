# Handleiding: Campagne Aanmaken in MM-AI Platform

## Overzicht
Het MM-AI platform biedt een gestroomlijnd proces voor het aanmaken van Meta marketing campagnes met AI-ondersteuning. Er zijn twee hoofdmethoden:

1. **Via Campaign Briefing** (aanbevolen) - Gebruik AI om campagnes te genereren
2. **Handmatig via Campaign Draft** - Directe controle over alle details

---

## Methode 1: Campagne Aanmaken via Campaign Briefing (AI-Assisted)

### Stap 1: Campaign Briefing Aanmaken

1. **Navigeer naar Campaign Briefings**
   - Log in op het MM-AI platform
   - Ga naar het menu → **Campaign Builder** → **Briefings**
   - Klik op **Create New Briefing**

2. **Vul de Briefing In**

   **Basis Informatie:**
   - **Brand**: Merknaam (bijv. "Nike", "Coolblue")
   - **Market**: Doelmarkt (bijv. "Netherlands", "Belgium")
   - **Objective**: Campagnedoel (bijv. "CONVERSIONS", "TRAFFIC", "AWARENESS")
   - **Product Name**: Productnaam die je promoot

   **Targeting & Doelstellingen:**
   - **Target Audience**: Beschrijf je doelgroep
     ```
     Voorbeeld: "Mannen 25-45 jaar, geïnteresseerd in fitness en sport,
     woonachtig in Nederland, mid-high income"
     ```

   - **Campaign Goal**: Wat wil je bereiken?
     ```
     Voorbeeld: "Genereer 500 online aankopen met een target ROAS van 3.0
     binnen 30 dagen"
     ```

   **Budget & Landing Page:**
   - **Budget Amount**: Totaal campagnebudget in EUR (bijv. 5000.00)
   - **Landing Page URL**: Volledige URL van de bestemmingspagina
     ```
     Voorbeeld: https://www.example.com/products/summer-sale
     ```

   **Optioneel:**
   - **Notes**: Extra context, beperkingen, of specifieke wensen

3. **Opslaan**
   - Klik op **Create Briefing**
   - De briefing krijgt status "draft"

### Stap 2: Campaign Draft Genereren

1. **Selecteer Template**
   - Open je briefing (klik op de briefing in de lijst)
   - Klik op **Generate Draft**
   - Selecteer een **Campaign Template** die past bij:
     - Je brand
     - Je market
     - Je objective
     - Je funnel stage

2. **AI Generatie**
   - Het systeem gebruikt OpenAI om:
     - Campaign structure te creëren (Ad Sets, Ads)
     - Creative content te genereren (copy, headlines)
     - Targeting parameters in te stellen
     - Budget allocatie te berekenen
   - Dit duurt enkele seconden

3. **Review Generated Draft**
   - De draft wordt automatisch aangemaakt met status "draft"
   - Bekijk alle gegenereerde elementen:
     - Campaign structuur
     - Ad Sets met targeting
     - Ad creatives met copy
     - Budget verdeling
     - UTM parameters

### Stap 3: Review Aanvragen

1. **Submit for Review**
   - Open de draft
   - Controleer alle details
   - Klik op **Request Review**
   - Status verandert naar "ready_for_review"

2. **Wacht op Approval**
   - Een reviewer zal de draft beoordelen
   - Je ontvangt feedback via het approvals systeem

### Stap 4: Approval Proces

1. **Als Reviewer:**
   - Ga naar **Approvals** in het menu
   - Open de approval request
   - Bekijk alle campaign details
   - **Approve** of **Reject** met notities

2. **Bij Approval:**
   - Draft status wordt "approved"
   - Campagne is klaar voor publicatie

3. **Bij Rejection:**
   - Draft status wordt "rejected"
   - Bekijk feedback
   - Pas draft aan indien mogelijk
   - Vraag opnieuw review aan

### Stap 5: Publiceren naar Meta

1. **Publish Initiëren**
   - Open de approved draft
   - Klik op **Publish to Meta**
   - Status wordt "publishing"

2. **Automatische Publicatie**
   - Het systeem creëert een Publish Job
   - De job wordt verwerkt in de achtergrond
   - Meta API calls worden uitgevoerd:
     - Campaign aanmaken
     - Ad Sets aanmaken
     - Ads aanmaken met creatives

3. **Monitor Progress**
   - Ga naar **Publish Jobs** in het menu
   - Bekijk status van je publish job:
     - **Pending**: Wacht op verwerking
     - **Running**: Wordt uitgevoerd
     - **Completed**: Succesvol gepubliceerd
     - **Failed**: Fout opgetreden

4. **Bij Success:**
   - Draft status wordt "published"
   - Campagne is live op Meta
   - Meta ID's worden opgeslagen

5. **Bij Failure:**
   - Bekijk error message in publish job
   - Klik op **Retry** om opnieuw te proberen
   - Of pas draft aan en maak nieuwe publish job

---

## Methode 2: Campagne Handmatig Aanmaken via Draft

### Direct Campaign Draft Maken

1. **Navigeer naar Campaign Drafts**
   - Menu → **Campaign Builder** → **Campaign Drafts**
   - Klik op **Create New Draft**

2. **Vul Draft Details In**
   - **Template**: Selecteer een campaign template (optioneel)
   - **Briefing**: Link aan briefing (optioneel)
   - **Draft Payload JSON**: Vul handmatig de campaign structuur in

   ```json
   {
     "campaign": {
       "name": "Summer Sale 2026",
       "objective": "CONVERSIONS",
       "status": "PAUSED",
       "special_ad_categories": [],
       "budget_optimization": true,
       "daily_budget": 100
     },
     "ad_sets": [
       {
         "name": "NL - Men 25-45",
         "targeting": {
           "age_min": 25,
           "age_max": 45,
           "genders": [1],
           "geo_locations": {
             "countries": ["NL"]
           }
         },
         "optimization_goal": "PURCHASE",
         "billing_event": "IMPRESSIONS",
         "bid_strategy": "LOWEST_COST_WITHOUT_CAP"
       }
     ],
     "ads": [
       {
         "name": "Ad 1 - Summer Sale",
         "creative": {
           "name": "Summer Creative 1",
           "object_story_spec": {
             "page_id": "YOUR_PAGE_ID",
             "link_data": {
               "link": "https://example.com/sale",
               "message": "Ontdek onze summer sale!",
               "name": "Tot 50% korting",
               "description": "Shop nu en profiteer",
               "call_to_action": {
                 "type": "SHOP_NOW"
               }
             }
           }
         }
       }
     ]
   }
   ```

3. **Volg Review & Publish Proces**
   - Zelfde als Methode 1, stap 3-5

---

## Campaign Templates Gebruiken

### Template Selecteren

**Templates zijn vooraf geconfigureerde campagne structuren:**

- **Template Eigenschappen:**
  - Brand (Nike, Coolblue, etc.)
  - Market (Netherlands, Belgium, etc.)
  - Objective (CONVERSIONS, TRAFFIC, etc.)
  - Funnel Stage (AWARENESS, CONSIDERATION, CONVERSION)
  - Default Budget
  - Campaign Structure (JSON)
  - Creative Rules (JSON)
  - UTM Template

**Template Voordelen:**
- ✅ Consistente campagne structuur
- ✅ Brand-specifieke instellingen
- ✅ Geoptimaliseerde targeting
- ✅ Gestandaardiseerde UTM parameters
- ✅ Snellere setup

### Nieuwe Template Aanmaken

1. **Navigeer naar Templates**
   - Menu → **Campaign Builder** → **Templates**
   - Klik op **Create New Template**

2. **Configureer Template**
   - Vul alle velden in (zie hierboven)
   - **Structure JSON**: Definieer standaard ad sets en ads
   - **Creative Rules JSON**: Definieer regels voor creatives
   - **Is Active**: Vink aan om te gebruiken

3. **Opslaan**
   - Template is nu beschikbaar bij briefing generatie

---

## UTM Parameters Configureren

### UTM Template Instellen

1. **Navigeer naar UTM Templates**
   - Menu → **Campaign Builder** → **UTM Templates**

2. **Template Pattern**
   ```
   utm_source=meta
   utm_medium=paid_social
   utm_campaign={campaign_name}
   utm_content={ad_name}
   utm_term={ad_set_name}
   ```

3. **Auto-Apply**
   - UTM parameters worden automatisch toegevoegd aan landing page URLs
   - Tracking in Google Analytics/andere tools

---

## AI Recommendations Gebruiken

### Performance & Structure Agents

**Het platform genereert automatisch aanbevelingen:**

1. **Performance Agent**
   - Analyseert campagne performance
   - Suggereert optimalisaties:
     - Budget aanpassingen
     - Bid strategie wijzigingen
     - Status changes (pauzeren, activeren)

2. **Structure Agent**
   - Analyseert campagne structuur
   - Suggereert verbeteringen:
     - Nieuwe ad sets
     - Targeting optimalisaties
     - Creative variaties

### Recommendations Beoordelen

1. **Ga naar Recommendations**
   - Menu → **Recommendations** → **Recommendations**

2. **Review Aanbeveling**
   - Bekijk severity (info, warning, critical)
   - Lees AI reasoning
   - Bekijk voorgestelde changes (JSON)

3. **Actie Ondernemen**
   - **Approve**: Aanbeveling goedkeuren voor uitvoering
   - **Reject**: Aanbeveling afwijzen met reden
   - **Ignore**: Aanbeveling negeren

4. **Automatische Uitvoering**
   - Approved recommendations met auto-execute worden automatisch uitgevoerd
   - Bekijk status in recommendations lijst

---

## Guardrail Rules & Safety

### Guardrails Begrijpen

**Guardrails beschermen tegen ongewenste acties:**

**Voorbeelden:**
- Max daily budget increase: 20%
- Min daily budget: €10
- Prevent pausing high-performing campaigns (ROAS > 3.0)
- Block changes during peak hours

### Guardrail Rule Aanmaken

1. **Navigeer naar Guardrails**
   - Menu → **Operations** → **Guardrail Rules**

2. **Create Rule**
   - **Name**: Beschrijvende naam
   - **Rule Type**: soft_limit, hard_limit, validation, time_restriction
   - **Scope**: campaign, ad_set, ad, budget, creative
   - **Expression**: Regel logica

   ```javascript
   // Voorbeeld: Max budget increase
   proposed.daily_budget <= current.daily_budget * 1.2
   ```

3. **Configureer**
   - **Priority**: 1-100 (hoger = belangrijker)
   - **Is Active**: Aan/uit
   - **Bypass Allowed**: Kan overruled worden?
   - **Error Message**: Bericht bij schending

---

## Scheduled Tasks & Automation

### Automatische Campagne Taken

1. **Navigeer naar Scheduled Tasks**
   - Menu → **Operations** → **Scheduled Tasks**

2. **Task Types**
   - **agent_run**: Run AI agents (Performance/Structure)
   - **sync_meta**: Sync Meta data
   - **report_generation**: Genereer rapporten
   - **custom_action**: Aangepaste actie

3. **Create Scheduled Task**
   - **Name**: Taak naam
   - **Task Type**: Selecteer type
   - **Cron Expression**: Planning
     ```
     0 9 * * *        # Dagelijks om 09:00
     0 */6 * * *      # Elke 6 uur
     0 0 * * 1        # Elke maandag om middernacht
     ```
   - **Payload**: Task configuratie (JSON)
   - **Is Active**: Aan/uit

---

## Executive Reports & KPI Cockpit

### Executive Reports Bekijken

1. **Ga naar Reports**
   - Menu → **Reports** → **Executive Reports**

2. **Report Types**
   - **Daily Summary**: Dagelijkse performance
   - **Weekly Performance**: Wekelijks overzicht
   - **Custom**: Aangepaste periode

3. **Report Inhoud**
   - Headline metrics (spend, revenue, ROAS)
   - Highlights (successen)
   - Issues (aandachtspunten)
   - Top/Bottom performers
   - Priorities (acties)

### KPI Cockpit Monitoren

1. **Open KPI Cockpit**
   - Menu → **Dashboard & Operations** → **KPI Cockpit**

2. **Real-time Metrics**
   - Active campaigns, ad sets, ads
   - Total spend & revenue
   - Conversions & ROAS
   - Pending recommendations
   - Open alerts
   - Trends (vs vorige dag)

---

## Veelvoorkomende Workflows

### Workflow 1: Quick Campaign Launch

```
1. Create Briefing → 2. Generate Draft → 3. Request Review
→ 4. Approve → 5. Publish → ✓ Live
```

**Tijdsduur**: 10-15 minuten

### Workflow 2: Optimization Cycle

```
1. Check KPI Cockpit → 2. Review Recommendations
→ 3. Approve Changes → 4. Monitor Performance → 5. Repeat
```

**Frequentie**: Dagelijks

### Workflow 3: Weekly Review

```
1. Open Weekly Executive Report → 2. Identify Issues
→ 3. Review Bottom Performers → 4. Create Action Plan
→ 5. Implement Changes
```

**Frequentie**: Elke maandag

---

## Tips & Best Practices

### Campagne Setup
✅ Gebruik descriptieve namen voor campagnes, ad sets en ads
✅ Start campagnes met status "PAUSED" en activeer na review
✅ Gebruik UTM templates voor consistente tracking
✅ Test meerdere creatives per ad set (A/B testing)
✅ Monitor eerste 48 uur intensief na launch

### Budget Management
✅ Start met conservatief budget
✅ Gebruik CBO (Campaign Budget Optimization) voor efficiency
✅ Monitor daily spend vs budget
✅ Respect guardrails voor budget changes

### AI Recommendations
✅ Review critical recommendations binnen 24 uur
✅ Trust the AI, but verify belangrijke changes
✅ Reject recommendations met duidelijke reasoning
✅ Monitor executed recommendations voor impact

### Performance Monitoring
✅ Check KPI Cockpit dagelijks
✅ Set up alerts voor critical issues
✅ Review weekly performance reports
✅ Compare trends week-over-week

---

## Troubleshooting

### Publish Job Failed

**Probleem**: Publish job heeft status "failed"

**Oplossingen:**
1. Bekijk error message in publish job details
2. Check Meta API access tokens (Settings)
3. Verify Meta account permissions
4. Check Meta pixel/page configuration
5. Retry publish job

### Geen Recommendations

**Probleem**: Geen AI recommendations beschikbaar

**Oplossingen:**
1. Run Performance Agent: `php artisan agents:run-performance-agent`
2. Run Structure Agent: `php artisan agents:run-structure-agent`
3. Check dat campaigns Meta insights hebben
4. Verify OpenAI API key (Settings)

### Guardrail Blocked Action

**Probleem**: Actie geblokkeerd door guardrail

**Oplossingen:**
1. Review guardrail rule
2. Pas actie aan binnen grenzen
3. Request bypass van admin (indien toegestaan)
4. Override guardrail (met goede reden)

### Scheduler Not Running

**Probleem**: Scheduled tasks worden niet uitgevoerd

**Oplossingen:**
1. Check Laravel scheduler: `php artisan schedule:list`
2. Verify cron is configured: `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`
3. Check scheduled task is active
4. Review task run history voor errors

---

## Support & Contact

**Platform Issues:**
- Check System Alerts: Menu → Operations → System Alerts

**Vragen over Campagne Setup:**
- Review deze handleiding
- Check Executive Reports voor insights

**Technical Support:**
- Contact platformbeheerder
- Review Laravel logs: `storage/logs/laravel.log`

---

**Laatste update**: 19 maart 2026
**Platform versie**: Laravel 12 + Sprint 1-5 Complete
