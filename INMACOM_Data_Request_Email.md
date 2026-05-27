# INMACOM IMS Revitalisation — Data and User Provisioning Request

Below is the complete, formatted draft of the email to be sent to the INMACOM representatives and national focal points. You can copy and paste this markdown text directly into your email client or document.

***

**Subject:** Technical Request: INMACOM IMS V2 Data Verification and User Provisioning for Council of Ministers Meeting (4-5 June 2026)

**Dear INMACOM Technical Representatives and Focal Points,**

As we prepare for the high-level **Council of Ministers meeting on 4 and 5 June 2026**, the Datamatics MIS Team has successfully developed and compiled the core spatial engine and the eight-module treaty-anchored framework for the revitalised **INMACOM Information Management System (IMS) V2**.

To ensure that the live, navigable platform presented to the Ministers is complete, verified, and officially sanctioned, we need to address several key uncertainties in our database. Most of our station data is currently based on legacy SQL records and automated algorithmic inferences, which must be officially confirmed. 

Additionally, we need to provision the initial accounts for system operators across Eswatini, South Africa, and Mozambique to establish the treaty-mandated **verification workflow**.

We kindly request that your technical teams start today and get this information to us as soon as possible, so we can begin importing verified data into the staging platform immediately.

---

### PART 1: Verification of Inferred Station Metadata

We successfully parsed the legacy station registry (`station.sql`), mapping **94 distinct monitoring stations** across the Incomati and Maputo basins. Because the legacy database lacked crucial technical attributes, our system programmatically **inferred (guessed)** several parameters (such as subcatchments, water body types, owners, and status). 

All these stations are currently flagged on the map as **"Inferred Metadata — Unconfirmed & Unverified"** (marked with a high-visibility yellow alert). To clear these disclaimers and officially verify them, we require confirmation of the following metadata parameters for each station:

#### Required Station Metadata Review File
To make this review as simple as possible, we have generated a pre-filled spreadsheet containing all **94 stations** currently loaded in the database, clearly showing which parameters are currently **unconfirmed/inferred** (marked as *'UNCONFIRMED'*). 

Please review, edit, and correct these parameters directly within the attached **`INMACOM_Stations_Review_Template.csv`** file. The spreadsheet contains the following columns for review:

| Column Header | Accepted Values / Description | Example |
| :--- | :--- | :--- |
| **Station Code** | Official alphanumeric code (Unique Identifier) | `GS-39` |
| **Station Name** | Official full name of the station | `Mnjoli Dam` |
| **Latitude** | WGS-84 Decimal Degrees (up to 6 decimal places) | `-26.1333` |
| **Longitude** | WGS-84 Decimal Degrees (up to 6 decimal places) | `31.0900` |
| **Operational Category** | `river_gauge` \| `dam` \| `borehole` \| `rainfall_station` \| `lake` \| `wetland` \| `other` | `dam` |
| **Water Source** | `surface` \| `groundwater` \| `precipitation` | `surface` |
| **Water Body Type** | `river` \| `dam` \| `borehole` \| `lake` \| `wetland` | `dam` |
| **River Basin** | `Incomati` \| `Maputo` | `Maputo` |
| **Official Subcatchment** | One of the 15 IIMA subcatchments (e.g. `MAP-USUTHU`, `INC-KOMATI`, etc.) | `MAP-USUTHU` |
| **Owner Organization** | `DWA-SW` \| `DWA-RSA` \| `ARA-Sul` \| `KOBWA` \| `TPTC` | `DWA-SW` |
| **Operational Status** | `active` \| `inactive` | `active` |
| **Real-time Telemetry** | `Yes` (real-time automated) \| `No` (manually read) | `Yes` |

#### Specific Critical Uncertainties to Address:

1. **Bela Vista Station (`E-3`) coordinates**: 
   * *Issue*: In the legacy SQL dump, the coordinates for the **Bela Vista** gauge in Mozambique were set to `0.0, 0.0` (placing it in the Atlantic Ocean near Congo, known as Null Island). 
   * *Resolution*: We have temporarily corrected these coordinates in our system to **Latitude: -26.34070, Longitude: 32.66750** based on river geographic models. **Please officially confirm if these coordinates are correct** or provide the exact geographic coordinates for the Bela Vista river gauge.
2. **Deduplication and Double Capabilities**:
   * *Issue*: In the legacy registry, several stations were listed multiple times under different modules (e.g., `GS-34` listed separately for River Flow and Water Quality). We have merged these duplicates into single physical stations with multiple capability profiles (e.g., a single borehole that records both groundwater depth and water-quality parameters).
   * *Action*: Please review our merged station list in the attached **`INMACOM_Stations_Review_Template.csv`** spreadsheet and confirm that no physical station is duplicated or split into multiple entries.

---

### PART 2: User Provisioning and Access Control

To comply with **Section 2.2 of the DDR (Iteration 2)**, the IMS V2 enforces a secure, three-tier role-anchored access scheme. Data is segmented by national jurisdiction, and data entry is subjected to a strict **Verification Gate**:

1. **Data Clerk (Field Data-Entry Staff)**: Can enter daily measurements (flow, dam levels, WQ, rainfall) and log local disaster incidents. They *cannot* verify or approve any data, including their own.
2. **Data Manager (Senior National Technical Staff)**: Reviews and verifies pending entries from their country's Clerks. Once a Data Manager approves an entry, it is marked as "Verified" and immediately populates the public dashboards, compliance reports, and Treaty indicators. Data Managers can enter data themselves, but approving their own data triggers an automatic, audit-logged override for transparency.
3. **Admin (System Administrator)**: Manages user accounts, configures reference datasets (such as IIMA targets and thresholds), and maintains system-wide audits.

#### Required User Provisioning Format
To populate the system's database and generate secure Firebase registration PINs, please fill out the attached **`INMACOM_User_Provisioning_Template.csv`** spreadsheet. This file is pre-formatted with clean column structures and reference examples. 

Please provide the details of all staff members from **Mozambique, South Africa, Eswatini, and KOBWA** who will be using the system directly in this template:

| Full Name | Email Address | Country Representation | Employer Organization | Intended System Role |
| :--- | :--- | :--- | :--- | :--- |
| *e.g., Datamatics Admin* | *mis@datamatics.co.sz* | *Eswatini* | *DWA-SW* | *Data Manager* |
| | | | | |
| | | | | |
| | | | | |

*Note: Once these emails are pre-registered, the system will auto-generate unique single-use registration codes (PINs) linked to their designated roles, which we will distribute to each user to complete their secure Firebase authentication.*

---

### Importance of This Data

Having complete, officially verified station metadata and a clean list of authorized national operators is key for the upcoming Ministers' meeting. It will show the Council of Ministers that:
* The three countries are collaborating with absolute trust and transparency.
* Information is being entered and validated by authorized national officers under strict institutional protocols.
* The system is a robust, production-ready spatial platform and not just a prototype.

We stand ready to hop on a technical session to assist in compiling this list. Thank you for your continued collaboration in revitalising the transboundary water-resources management of the Incomati and Maputo basins.

Best regards,

**Datamatics MIS Team**  
*mis@datamatics.co.sz*

***
