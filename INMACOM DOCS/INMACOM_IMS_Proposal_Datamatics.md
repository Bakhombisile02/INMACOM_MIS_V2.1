---
title: "Proposal — Revitalisation of the INMACOM Information Management System"
subtitle: "From a delivered platform to a treaty-anchored foundation"
author: "Datamatics Eswatini · Bakhombisile Siyamukela Dlamini, Systems Consultant"
date: "May 2026"
---

# Title Page

**Proposal — Revitalisation of the INMACOM Information Management System**

*From a delivered platform to a treaty-anchored foundation that the Commission, the VUNWE programme and the INMACOM hackathon can build upon for the rest of the decade.*

| Field | Value |
|---|---|
| Prepared for | Incomati and Maputo Watercourse Commission (INMACOM) |
| Prepared by | Datamatics Eswatini |
| Lead consultant | Bakhombisile Siyamukela Dlamini, Systems Consultant |
| Quotation reference | DME-INMACOM-2026-Q01 |
| Companion documents | *Database Design Report — Iteration 2*; *Quotation* |
| Date of issue | May 2026 |
| Validity | 60 days from date of issue |
| Final delivery date | 29 May 2026 |
| Currency | Eswatini Emalangeni (E), exclusive of VAT |

---

# 1. Executive Summary

**The problem.** The platform that INMACOM operates today at *inmacom.net* was developed and delivered by Datamatics Eswatini in an earlier engagement and handed over to the Commission on completion. In the years that followed, operational knowledge of the platform concentrated in a small number of people who later moved on; their successors were not trained against the platform; routine data updates lapsed. The platform persisted as an institutional asset but, in operational terms, drifted out of routine use. The cause was not in the software — it was in the knowledge-management environment around it.

**The proposal.** Datamatics Eswatini returns to that work — with the addition of new specialist capacity to the team — to revitalise the platform as a treaty-anchored **foundation**. The foundation comprises an eight-module database pre-loaded from the IIMA, IAAP-5, IAAP-10 and REIWQ source instruments; an enforced verification workflow with a permanent audit trail; a public-facing dashboard in English, Portuguese and siSwati; bilateral data-exchange APIs operating to the REIWQ cadence; and a silo-resistant knowledge-transfer package. Full capability detail is in the *Database Design Report (Iteration 2)*.

**The pricing.** Total professional fees of **E117,600 (exclusive of VAT)** for the foundation scope, delivered against the **29 May 2026** final-submission date. Full breakdown — work items, hours, resource, rate and milestones — is in the accompanying *Quotation* (DME-INMACOM-2026-Q01).

**Why Datamatics.** Datamatics Eswatini built the platform. The lead consultant on this engagement, Bakhombisile Siyamukela Dlamini, led the Iteration 1 database design and the present re-examination. The team is closer to this codebase, its history and its treaty context than any other vendor available to the Commission.

---

# 2. About INMACOM

The **Incomati and Maputo Watercourse Commission** is the joint inter-governmental body of **Mozambique, South Africa and Eswatini** for the shared management of the Incomati and Maputo watercourses. The Commission's mandate is given legal substance by the **Interim IncoMaputo Agreement (IIMA)** signed on 29 August 2002 and the **Resolution of the TPTC on the Exchange of Information and Water Quality (REIWQ)** signed on 13 August 2002. INMACOM operates at the technical level through the **Tripartite Permanent Technical Committee (TPTC)**, supported by national focal institutions in each riparian state — among them DNA and ARA-Sul in Mozambique, DWA-RSA, ICMA and KOBWA in South Africa, and MNRE, DWA-SW and SWADE in Eswatini.

The Commission's daily work is information-intensive. It requires: monitoring of water-use against fixed entitlements at the subcatchment level; monitoring of minimum ecological flows at named cross-border points (the most consequential being Ressano Garcia, gauge X2H036 ‖ E-23, at 2.6 m³/s); coordinated response to floods, droughts and pollution incidents; and the structured exchange of verified data among the three Parties on a fixed cadence. A trustworthy information system is not adjacent to that mandate — it is the mandate's instrument.

---

# 3. Problem Statement

## 3.1 The Short History of the IMS

The story of INMACOM's information system has three chapters. Each is honest about what worked and what did not, because the present proposal is intelligible only against that lineage.

**2008 – 2011 · The PRIMA effort.** The Progressive Realisation of the IncoMaputo Agreement (PRIMA) programme commissioned a Management Information System under Project 11801376, executed by a consortium of DHI, SWECO, Consultec and BKS and funded by the Royal Netherlands Embassy. The consortium produced the data-exchange logic, the joint annual hydrological yearbook concept, and the prioritised station tiering that the present design still reaches back to as conceptual material. The platform itself did not endure as an operational system. The conceptual contribution survives; the running software did not.

**The current platform · *inmacom.net*.** Following the close-out of the PRIMA effort, Datamatics Eswatini was engaged to develop the IMS that the Commission operates today at *inmacom.net*. Datamatics designed and built the platform, populated it with the initial reference data available at the time, and handed it over to INMACOM on completion. The platform is, in itself, a credit to the work — it is the platform's *use*, not its build, that the present proposal addresses.

**Underutilisation through information silos.** In the years between hand-over and today, the operational knowledge of the platform inside INMACOM concentrated in a small number of people. Those people moved on. Their successors were not trained against the platform. The data updates that would have kept it current — the monthly flow logs, the quarterly water-quality samples, the disaster-incident records, the annual water-use returns — were not routinely made. The platform persisted as an institutional asset; in practical terms, it was underutilised.

## 3.2 The Three Operational Gaps

A focused re-examination by the Datamatics team — documented in full in Section 1.3 of the *DDR (Iteration 2)* — established three gaps that the foundation must close.

a. **The treaty-anchored content is partial.** The Maputo Basin water-use allocations and ecological-flow key points, the four-level disaster alert scheme, the IAAP-5 indicator scoring framework (Flood Status Weight; Drought Score; pollution classification via US EPA Reportable Quantities), the IAAP-10 three-level restriction framework, the dual-code identity of the cross-border compliance point at Ressano Garcia (E-23 ‖ X2H036, 2.6 m³/s), and the REIWQ Appendix A water-quality parameter list are either incomplete in the current platform or absent altogether.

b. **The verification discipline is not yet operationally enforced.** Without an enforced `pending → approved` gate held by Data Managers, and without a permanent Audit Log of self-approvals and overrides, the integrity of the data the Commission exchanges with the three Parties is not defensible. This is the single most consequential gap and the gap most directly responsible for the underutilisation described above.

c. **The platform is not yet silo-resistant.** Documentation, multilingual presentation, role-anchored accountability and a treaty-anchored data load that is meaningful from day one — these are the institutional disciplines that make a platform recoverable when the people who built it move on. They are addressed here as deliverables, not as goodwill.

## 3.3 Why Datamatics

The 2008–2011 architectural thinking that survives in the current platform is sound where it survives — the eight-module structure, the verification gate between unverified and verified data, the three-role access scheme. Datamatics Eswatini built that platform. The team that delivered it is the team available now to revitalise it, supplemented by the additional specialist capacity that conducted the 2026 re-examination.

---

# 4. Proposed Solution

A revitalised IMS that *is* the treaty implementation, not merely a database that supports it. The scope of this engagement — the **foundation** — is summarised below. Capability detail is in the *DDR*.

## 4.1 Database — treaty-anchored, pre-loaded

a. The eight-module database as specified in the *DDR*.
b. All fifteen IIMA subcatchments pre-loaded with the IAAP-10 codes — seven in the Incomati Basin, eight in the Maputo Basin — with the Iteration 1 Maputo-subcatchment naming errors corrected.
c. IIMA water-use allocations, both basins, by country and by user category, totalling 2,251 Mm³/a (Incomati) and 1,697 Mm³/a (Maputo), reproduced verbatim from IAAP-10 Tables 5-1 and 5-3.
d. All nineteen IIMA ecological-flow key points (nine Incomati, ten Maputo) linked to their gauges, including the Ressano Garcia dual-code identity and the 2.6 m³/s cross-border minimum.
e. The 37 REIWQ Appendix A water-quality parameters with default guideline values acting as fall-back compliance thresholds.
f. The IAAP-5 hazard scoring tables for floods and droughts (four indicators each, confidence-weighted), the US EPA Reportable Quantity pollution-classification tiers, and the conservative-default rule for unlisted substances.
g. The four-phase IAAP-5 incident response model and the three-country institutional notification matrix.

## 4.2 Application — the base that operates the database

a. The enforced verification workflow: every measurement enters `pending`, transitions to `approved` or `rejected` by a Data Manager, with self-approvals permitted but permanently audit-logged.
b. The three-role access scheme: Admin, Data Manager, Data Clerk — and a fourth implicit role of *public dashboard viewer*, who sees only verified data.
c. The public-facing dashboard, styled for the Commission and presented in the three working languages of the basin: **English, Portuguese and siSwati**.
d. The bilateral data-exchange APIs that operationalise the REIWQ cadence: three months for sample analyses, six months for new posted data, twelve months for existing data, annual per-Party reports.
e. The compliance-checking layer that compares every approved flow reading at an IIMA key point against the IIMA minimum and surfaces non-compliance automatically.
f. The disaster-management workspace that captures incidents under the four-phase IAAP-5 response and dispatches structured notifications to the named institutional recipient in each country.
g. The document and audit modules, completing the eight-module architecture.

## 4.3 Knowledge transfer — built to resist the silo

a. Operator hand-over documentation sufficient for the foundation to be operated independently by INMACOM-nominated staff in all three countries — written for the *next* generation of operators, not only the present one.
b. A working session with the TPTC Working Group covering the verification workflow, the compliance dashboard and the disaster module.
c. A documented set of acceptance criteria (Section 9.2 of the *DDR*) that the Commission can re-run year-on-year as a health check on the platform.

## 4.4 Evaluation metrics

Acceptance is judged against the nine criteria in Section 9.2 of the *DDR*: fifteen subcatchments loaded; allocations matching IAAP-10; nineteen ecological-flow points linked; 37 parameters confirmed; indicator rating tables loaded; EPA RQ tiers loaded; notification matrix populated; verification workflow exercised by INMACOM-nominated staff; Audit Log visibility policy confirmed.

## 4.5 The durable returns

a. **Defensible data.** Every figure the Commission cites bilaterally is traceable from the dashboard back through an `AuditLog` entry to a named Data Manager.
b. **Treaty alignment by construction.** Compliance with the IIMA minima and the REIWQ exchange cadence is computed by the system, not assembled by hand at the end of each reporting cycle.
c. **A platform other partners can build on.** Modular architecture, natural and stable keys, uniformly enforced verification gate — subsequent expansion by VUNWE, the INMACOM hackathon, or other partners is a matter of extension, not re-engineering.

---

# 5. Pricing

Total professional fees for the foundation scope are **E117,600 (excl. VAT)**, on an hourly-rate basis across 96 hours of effort. The summary is reproduced below; the full line-item breakdown, the rate card and the payment schedule are in the accompanying *Quotation* (DME-INMACOM-2026-Q01).

| Resource | Hours | Rate (E) | Subtotal (E) |
|---|---:|---:|---:|
| Lead Systems Consultant | 20 | 1,500 | 30,000 |
| Senior Database & Application Specialist | 64 | 1,200 | 76,800 |
| Documentation & Knowledge-Transfer Specialist | 12 | 900 | 10,800 |
| **Total (excl. VAT)** | **96** | | **E117,600** |

Payment is staged 40 % on mobilisation, 30 % at end of Week 1, 30 % on final submission against the *DDR* §9.2 acceptance criteria. No ongoing expenses are payable to Datamatics during or after the foundation engagement; subsequent work is contracted separately under the wider programme (Section 8).

---

# 6. Timeline

The engagement runs across a compressed twelve-day window. The schedule below is binding.

| Phase | Window | Activities |
|---|---|---|
| Foundation hardening | 18 – 24 May 2026 | Paperwork, data preparation, database finalisation, application development and improvement on the existing *inmacom.net* base. |
| Presentation and polish | 25 – 29 May 2026 | Application presentation, testing, polishing, final refinements, final submission package. |
| Submission | 29 May 2026 | Final delivery to INMACOM. |

A working session with the TPTC Working Group is scheduled within the second week. Comments arising from that session are incorporated before final submission.

---

# 7. Qualifications

## 7.1 Datamatics Eswatini

Datamatics Eswatini built the IMS that INMACOM operates today at *inmacom.net*. The current re-examination, the *Database Design Report (Iteration 2)* and the foundation scope offered in this proposal are the work of the same firm, augmented by additional specialist capacity engaged specifically for the 2026 engagement.

## 7.2 The team on this engagement

**Lead consultant: Bakhombisile Siyamukela Dlamini, Systems Consultant — INMACOM IMS.** Lead author of the Iteration 1 database design (May 2026) and the Iteration 2 re-examination. Carries the lead-consultant role through Week 1 set-up, Week 2 presentation and the final submission package.

**Senior Database & Application Specialist.** Carries the eight-module schema reconciliation, the treaty data pre-load (IIMA / IAAP-5 / IAAP-10 / REIWQ), the verification-workflow enforcement, the public dashboard, the bilateral APIs and the compliance + disaster modules wiring.

**Documentation & Knowledge-Transfer Specialist.** Carries the operator hand-over documentation in English with summary in Portuguese and siSwati, training materials and the year-on-year health-check guide.

## 7.3 Track record on this codebase

a. **The current production platform.** Designed, built and handed over by Datamatics. INMACOM operates it today. The team available to the Commission for this engagement knows the codebase, its data model and its treaty context first-hand.
b. **The *Database Design Report (Iteration 1)*, May 2026.** The same lead consultant authored the prior version. The natural-key discipline, the eight-module structure, the controlled-vocabulary approach and the verification workflow originate from that document.
c. **The *Database Design Report (Iteration 2)*, May 2026.** Submitted alongside this proposal. Adds the treaty-anchoring crosswalk, the IAAP-5 hazard scoring tables, the corrected Maputo subcatchment codes, the multilingual dashboard specification and the bilateral API design.

## 7.4 Treaty-instrument competence

The team's working knowledge of the source instruments — the IIMA (2002), the REIWQ (2002), IAAP-5 (Disaster Management, SRK Consulting, 2011) and IAAP-10 (System Operating Rules, Aurecon, 2011) — is documented in the *DDR (Iteration 2)*. Every entity in the proposed database is traceable to the article, section or table of the instrument that mandates it; Section 7 of the *DDR* presents the entity-to-treaty crosswalk in full.

---

# 8. The Wider Programme — VUNWE and the Hackathon

The foundation that this proposal offers is the starting point of a larger journey, not the end of one. The **VUNWE** programme — *Vitalising, Unifying, Neighbouring Water-management Empowerment* — exists to support INMACOM in the longer work of building cooperation, climate resilience and equitable water sharing across Eswatini, Mozambique and South Africa. The **INMACOM hackathon** brings further engineering capacity to bear on the platform.

Datamatics Eswatini regards the foundation set out in this proposal as a contribution to that wider programme. The architecture, the natural-key discipline, the verification gate and the entity-to-treaty crosswalk are designed so that the VUNWE team and the hackathon participants can build on the base — telemetry feeds from KOBWA, SADC-HYCOS, DWA-RSA, DNA, ARA-Sul and SWADE; a mobile field-entry application; advanced analytics over the verified-data archive — without re-engineering what we deliver. Datamatics intends to remain engaged, on appropriate terms, as a constructive technical partner to that work.

---

# 9. Conclusion

INMACOM has a platform. It is *inmacom.net*; it was built and handed over by Datamatics Eswatini; the architecture is sound where it survives; and the Commission already owns it. The platform is underutilised because the institutional disciplines that keep a platform alive — current data, trained staff, verified-data discipline, multilingual presentation, treaty-anchored content from day one — were not put in place around it.

This proposal sets out to put those disciplines in place. It is a focused, twelve-day engagement that delivers the foundation against a binding 29 May 2026 submission date, for total professional fees of **E117,600**. It leaves the Commission with a platform that is harder to silo, defensible bilaterally, aligned to the IIMA and the REIWQ by construction, and ready for the wider VUNWE / hackathon programme to build upon.

The next step is acceptance. On signature of Section 12, the engagement begins, the *Quotation* mobilisation invoice is issued and the team starts work. Questions on any section of this proposal can be directed to the lead consultant at the contact details on the title page.

---

# 10. Appendix

## A. Companion Documents

| Document | Reference | Purpose |
|---|---|---|
| Database Design Report — Iteration 2 | DDR v2, May 2026 | Full technical specification of the foundation; entity-to-treaty crosswalk; acceptance criteria (§9.2). |
| Quotation | DME-INMACOM-2026-Q01 | Full breakdown of work items, hours, resource, rate, milestones and payment schedule. |

## B. What We Need from INMACOM

To complete the foundation on schedule, three inputs are requested from the Commission. These are also documented in Section 6 of the *DDR*.

a. The operational station registry — name, code, alt-code, coordinates, owner, water source, capabilities, real-time flag — for the Incomati and Maputo basins.
b. Station-specific compliance thresholds for water-quality parameters where these exist; default REIWQ Appendix A values are applied where they do not.
c. The named focal point per country per hazard type for the disaster notification matrix.

Inputs are requested by **22 May 2026** to remain on schedule.

## C. Glossary of Key Acronyms

| Acronym | Expansion |
|---|---|
| IIMA | Interim IncoMaputo Agreement (2002) |
| REIWQ | Resolution on the Exchange of Information and Water Quality (2002) |
| TPTC | Tripartite Permanent Technical Committee |
| VUNWE | Vitalising, Unifying, Neighbouring Water-management Empowerment |
| IAAP-5 | *Disaster Management in the Incomati and Maputo Watercourses*, SRK Consulting, 2011 |
| IAAP-10 | *System Operating Rules*, Aurecon, 2011 |
| PRIMA | Progressive Realisation of the IncoMaputo Agreement |
| IMS | Information Management System |
| DDR | Database Design Report |

The full glossary is in Section 1.5 of the *Database Design Report (Iteration 2)*.

---

# 11. Terms and Conditions

The following terms and conditions form part of this proposal and, on acceptance under Section 12, become binding on the Parties together with the accompanying *Quotation*.

**11.1 Engagement.** Datamatics Eswatini ("Datamatics") agrees to deliver the foundation scope described in Section 4 of this proposal and specified in full in the *Database Design Report (Iteration 2)*, against the schedule in Section 6 and at the fees set out in Section 5 and the accompanying *Quotation*.

**11.2 Fees and payment.** Total professional fees are E117,600, exclusive of VAT, in Eswatini Emalangeni. Payment is staged 40 % on mobilisation, 30 % at the end of Week 1 against the foundation-hardening milestone, and 30 % at final submission against the *DDR* §9.2 acceptance criteria. Each milestone invoice is payable within 30 days of issue.

**11.3 Validity.** This proposal and the accompanying *Quotation* are valid for **60 days** from the date of issue.

**11.4 Change control.** Any addition or substitution to the scope requested after acceptance is handled under Section 6 of the *Quotation*: a written change request from INMACOM, a Change Note from Datamatics within five working days at the rates in the Quotation's Section 2, and written approval from INMACOM before work on the change begins.

**11.5 INMACOM inputs.** INMACOM provides the three inputs listed in Appendix B by 22 May 2026. Datamatics' ability to hold the 29 May 2026 final-submission date depends on timely delivery of those inputs; delay in inputs may result in a corresponding delay in submission, addressed under Section 11.4.

**11.6 Out of scope.** Telemetry integration with KOBWA, SADC-HYCOS, DWA-RSA, DNA, ARA-Sul and SWADE, and a mobile field-entry application, are expressly outside this engagement. Both are addressed by the wider VUNWE / hackathon programme.

**11.7 Cancellation.** Either Party may terminate the engagement on seven calendar days' written notice. On termination, Datamatics is paid for work completed up to the date of termination, calculated at the hours expended against the rates in the Quotation's Section 2, capped at the total fee.

**11.8 Warranty.** Datamatics warrants that the foundation as delivered meets the nine acceptance criteria in *DDR* §9.2. Defects identified within 30 days of final submission and arising from those criteria are remediated by Datamatics at no additional cost. The warranty does not extend to changes made by INMACOM or third parties to the platform after hand-over, or to scope items handled under Section 11.4.

**11.9 Confidentiality.** Each Party treats non-public information of the other as confidential. The Tausi-authored Tender No. 3 of 2024 report referenced in the *DDR* is treated as confidential and is not quoted or relied upon in any deliverable.

**11.10 Intellectual property.** The foundation as delivered (source code, database schema, pre-loaded reference data, documentation) is licensed to INMACOM for its use in fulfilling its mandate under the IIMA and the REIWQ. Datamatics retains ownership of its underlying methods, templates and pre-existing toolchain.

**11.11 Governing law.** This engagement is governed by the laws of the Kingdom of Eswatini. Disputes are referred first to good-faith negotiation between the lead consultant and the Executive Secretary of INMACOM, and thereafter, if unresolved, to arbitration in Mbabane.

**11.12 Legal review.** INMACOM is encouraged to have these terms reviewed by its own legal counsel before signature. Reasonable amendments proposed by INMACOM are accommodated by Datamatics in writing prior to acceptance.

---

# 12. Acceptance

The Parties accept this proposal and the accompanying *Quotation* and *Database Design Report (Iteration 2)* on the terms set out above, and instruct Datamatics Eswatini to commence the engagement.

| For Datamatics Eswatini | For the Incomati and Maputo Watercourse Commission |
|---|---|
| | |
| Name: Bakhombisile Siyamukela Dlamini | Name: ________________________________ |
| Title: Systems Consultant — INMACOM IMS | Title: ________________________________ |
| Signature: ____________________________ | Signature: ____________________________ |
| Date: ________________________________ | Date: ________________________________ |

**Contact for queries.** Bakhombisile Siyamukela Dlamini, Systems Consultant — INMACOM IMS, Datamatics Eswatini.

---

*Submitted on behalf of Datamatics Eswatini · May 2026.*
