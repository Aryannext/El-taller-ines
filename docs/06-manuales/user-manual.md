# User manual · Puntada

**Deliverable:** DOC-22 · **System version:** deployed on September 22, 2026 · **APK:** 1.0.0 · [Versión en español](manual-de-usuario.md)

This manual is for the shop owner: the person who receives the garments, alters them, collects payments and hands them back. It explains how to install the app on a phone and how to do each task of the day.

**The app is in Spanish.** Button and screen names appear here in Spanish, as you will see them, followed by their meaning in English: **Guardar orden** (Save order).

The screenshots show the real system with **sample data**: Marta Rincón, Ana Beltrán and the other customers do not exist.

## Contents

1. [What the system does](#1-what-the-system-does)
2. [Installing the app](#2-installing-the-app)
3. [Signing in and out](#3-signing-in-and-out)
4. [The Hoy (Today) screen](#4-the-hoy-today-screen)
5. [Customers](#5-customers)
6. [Registering an order](#6-registering-an-order)
7. [Finding and viewing orders](#7-finding-and-viewing-orders)
8. [Working on garments](#8-working-on-garments)
9. [Payments](#9-payments)
10. [Handing orders back](#10-handing-orders-back)
11. [Notifying the customer on WhatsApp](#11-notifying-the-customer-on-whatsapp)
12. [Overdue and unclaimed orders](#12-overdue-and-unclaimed-orders)
13. [Cancelling an order](#13-cancelling-an-order)
14. [No internet connection](#14-no-internet-connection)
15. [Common problems](#15-common-problems)
16. [Protecting your customers' data](#16-protecting-your-customers-data)

## 1. What the system does

It replaces the notebook and memory. With it:

- every garment you receive is recorded, with its alteration, its price and up to 3 photos;
- every order has a **number** you write on the bag, so you know whose clothes they are even when they are mixed up in the corner;
- you know how much each customer owes, because deposits and payments are subtracted automatically, and how much money you received in the day, week or month;
- when an order is ready, the customer gets a **WhatsApp notice**;
- the **Hoy** (Today) screen tells you what needs your attention: payments due, late deliveries and notices.

**Words the system uses:**

| Word | Meaning |
| --- | --- |
| **Orden** (Order) | Everything a customer leaves in one visit. It has a number, such as #0042 |
| **Prenda** (Garment) | Each piece of clothing in the order, with its alteration and price |
| **Garment status** | Pendiente (Pending) → En proceso (In progress) → Terminada (Finished) → Entregada (Delivered) |
| **Order status** | Worked out from its garments: **En proceso** (In progress), **Lista** (Ready, everything finished), **Entregada** (Delivered) or **Cancelada** (Cancelled) |
| **Saldo** (Balance) | What is still owed: the order's value minus what has been paid |
| **Aviso** (Notice) | The WhatsApp message telling the customer their clothes are ready |

## 2. Installing the app

You can use the system in the browser, but on a phone it is easier to install it: a **Taller** icon appears on the home screen and it opens full screen, like any app.

### On Android: with the APK

1. On the phone, open **Chrome** and go to:
   **https://proyectosena.online/taller/descargas/taller.apk**
2. Chrome may warn that the file could be harmful. Tap **Download anyway**. The app comes from the same site as the system.
3. When the download finishes, tap **Open**.
4. The first time, Android says that for security it does not allow installing apps from that source. Tap **Settings**, turn on **Allow from this source** and go back.
5. Tap **Install**. If Play Protect asks about an app it does not recognise, tap **Install anyway**.
6. Open **Taller** from the home screen.

Button names vary slightly by phone brand and Android version, but the steps are the same.

> **If you see the browser bar for a couple of seconds when the app opens:** this happens when the phone's main browser is not Chrome, for example Brave. The app works the same. To make it open directly, set Chrome as the default browser in **Settings → Apps → Default apps → Browser**.

### On another phone or a computer: from the browser

1. Open **https://proyectosena.online/taller/** in Chrome or Edge.
2. Look for the install option: on a phone, in the **⋮ → Install app** menu (or **Add to home screen**); on a computer, the install icon in the address bar.
3. A **Taller** icon appears and opens the system in its own window.

On iPhone the system works in Safari, but installing it as an app has not been tested.

## 3. Signing in and out

<img src="capturas/01-iniciar-sesion.png" alt="Sign-in screen" width="260" align="right">

**Signing in:** type your **Usuario** (Username) and **Contraseña** (Password) and tap **Entrar** (Sign in). The person who installed the system gives you both.

- If either is wrong, the system says «Usuario o contraseña incorrectos» (Wrong username or password) without saying which one, so nobody can guess your username.
- After several failed attempts in a row you have to wait a few seconds before trying again. The message tells you how many.

**Signing out:** on **Hoy**, tap the settings icon (top right) and then **Cerrar sesión** (Sign out). Always do this if you lend your phone to someone or use a computer that is not yours.

**Changing your password:** in **Ajustes** (Settings), type your **Contraseña actual** (Current password), the **Nueva contraseña** (New password, at least 8 characters) and repeat it. Tap **Cambiar contraseña** (Change password).

<br clear="right">

## 4. The Hoy (Today) screen

<img src="capturas/02-panel.png" alt="Today screen with amount owed, overdue, unclaimed and notices" width="260" align="right">

It is the first thing you see after signing in. It shows what needs your attention:

| Card | What it tells you | When you tap it |
| --- | --- | --- |
| **Por cobrar** (To collect) | How much you are owed in total, across orders that are not cancelled | You see who owes you and how much you have received ([section 9](#9-payments)) |
| **Atrasadas** (Overdue) | How many orders passed their delivery date without being ready | You see which ones ([section 12](#12-overdue-and-unclaimed-orders)) |
| **Sin reclamar** (Unclaimed) | How many orders have been ready for more than 30 days without being picked up | You see which ones and how much they owe |
| **Avisos por enviar** (Notices to send) | How many WhatsApp notices did not go out on their own and need you to send them | You send them with one tap ([section 11](#11-notifying-the-customer-on-whatsapp)) |

**The bottom bar** is on almost every screen: **Hoy** (Today), **Órdenes** (Orders), **Nueva** (New, the round button to register an order), **Clientes** (Customers) and **Dinero** (Money).

<br clear="right">

## 5. Customers

<img src="capturas/03-clientes.png" alt="Customer list with search" width="260" align="right">

Tap **Clientes** (Customers) in the bottom bar.

**Searching:** type part of the name or the phone number. Accents do not matter: «marta rincon» finds Marta Rincón.

**Registering a customer:** tap **Registrar cliente** (Register customer), below the list under «¿No aparece?» (Not listed?). Only the **Nombre** (Name) and **Celular** (Mobile number) are needed.

- Notices are sent to that mobile number, so type it carefully.
- Several customers can share the same number, for example a mother and her daughter.

<br clear="right">

<img src="capturas/05-ficha-cliente.png" alt="Marta Rincón's record with her debt and orders" width="260" align="right">

**The customer record:** tap a customer to open it. It shows how much they **owe in total** and on which orders, and all their orders with their status and balance. A cancelled order shows «No suma a la deuda» (Not counted in the debt).

- **Nueva orden para…** (New order for…) registers an order with that customer already selected.
- **Correcting their details:** tap the pencil at the top right.

<br clear="right">

## 6. Registering an order

<img src="capturas/06-nueva-orden.png" alt="New order form" width="260" align="right">

Tap **Nueva** (New), the round button in the bottom bar.

1. **Cliente** (Customer): pick them from the list. If they are new, **register them before filling in the order**: tap **El cliente es nuevo** (New customer), save them and, on their record, tap **Nueva orden para…** (New order for…). Anything you had already typed in the order is not kept.
2. **Fecha de entrega acordada** (Agreed delivery date): the date you promised the customer. It can be today, but not earlier.
3. **Each garment:**
   - **Tipo de prenda** (Garment type): choose from the list. If it is not there, choose **Otro…** (Other) and type what it is; it will be added to the list for next time.
   - **Qué arreglo lleva** (Alteration): for example «Subir basta 3 cm» (Take up hem 3 cm).
   - **Precio** (Price): in pesos, without cents. You can type «15000» or «15.000».
   - **Fotos** (Photos, up to 3): **Tomar foto** (Take photo) opens the camera; **Galería** (Gallery) picks photos you already have. The system shrinks them so they do not use up your data. If a garment has no photo, the system reminds you: a photo helps you recognise it in the corner.
4. Did the customer leave more than one garment? Tap **Agregar otra prenda** (Add another garment). To remove one, tap its bin icon.
5. Tap **Guardar orden** (Save order).

<br clear="right">

<img src="capturas/07-orden-guardada.png" alt="Saved order with the number for the bag" width="260" align="right">

**Write the number on the bag.** After saving, the system shows the order number in large type, for example **#0046**. Write it on the bag or on a tag: that way you will know whose clothes they are even if they are in the same corner.

From there you can **Ver la orden** (View the order) or **Registrar otra orden** (Register another order).

Tapping **Guardar orden** twice in a row does not create two orders: the system saves only one.

<br clear="right">

## 7. Finding and viewing orders

<img src="capturas/08-ordenes.png" alt="List of orders in progress" width="260" align="right">

Tap **Órdenes** (Orders) in the bottom bar.

- **Tabs:** **En proceso** (In progress), **Listas** (Ready), **Entregadas** (Delivered) and **Canceladas** (Cancelled). The first two show how many there are.
- **Buscar orden por número** (Find order by number): type «42» or «#0042» and the system opens that order. It is the quickest way when the customer arrives with their number.

Each order in the list shows the customer, the garments, the delivery date and whether it is **Pagada** (Paid) or **Por cobrar** (Payment due).

<br clear="right">

<img src="capturas/09-detalle-orden.png" alt="Details of order #0042" width="260" align="right">

**The order details** have everything:

- at the top, the **number** and the customer (tap their name to open their record);
- the order status and whether it is paid, the date you received it and the delivery date;
- the **garments**, with their status, photos and price;
- the **money**: value, payments and balance;
- the **notices** sent to the customer;
- the buttons to **Entregar** (Hand back), **Registrar pago** (Record payment) and **Cancelar la orden** (Cancel the order).

**Viewing photos:** tap a garment's photos or **Ver fotos juntas** (See all photos). Tapping a photo shows it large, so you can compare it with the garments in the corner.

<br clear="right">

## 8. Working on garments

### Changing the status

<img src="capturas/11-acciones-prenda.png" alt="Changing a garment's status" width="260" align="right">

In the order details, tap **Cambiar estado** (Change status) on the garment. The system only offers the changes that are allowed:

| If the garment is | It can move to |
| --- | --- |
| **Pendiente** (Pending) | En proceso («La estoy arreglando», I am working on it) or Terminada (Finished) |
| **En proceso** (In progress) | Terminada (Finished) |
| **Terminada** (Finished) | En proceso, if it needs a touch-up |

- When you mark the **last** garment as Terminada, the order becomes **Lista para entregar** (Ready to hand back) and the customer gets the WhatsApp notice.
- If a garment in a ready order goes back to En proceso, the order is no longer ready, and if the notice had not gone out yet, it no longer will.
- **Entregada** (Delivered) is not set here: use the order's **Entregar** button ([section 10](#10-handing-orders-back)).

<br clear="right">

### Correcting the alteration, price or photos

<img src="capturas/13-corregir-prenda.png" alt="Correcting a garment" width="260" align="right">

In **Cambiar estado**, tap **Corregir arreglo, precio o fotos** (Correct alteration, price or photos).

- Change **Qué arreglo lleva** (Alteration) or the **Precio** (Price) and tap **Guardar cambios** (Save changes). The order's value and balance are recalculated automatically.
- The garment type cannot be changed.
- **An order cannot be worth less than what has already been paid.** If you lower a price below that, the system will not save it: first void the extra payment ([section 9](#9-payments)).
- **Photos:** add any missing ones, up to 3 per garment. They upload as soon as you pick them.
- A delivered garment, or one in a cancelled order, can no longer be corrected.

<br clear="right">

### Adding a garment you forgot to record

<img src="capturas/12-agregar-prenda.png" alt="Adding a garment to an order" width="260" align="right">

If the customer left a garment that did not make it into the order, you do not need a new order. In the order details, below the garments, tap **Agregar una prenda** (Add a garment), fill it in as in a new order and tap **Guardar prenda** (Save garment).

- The garment starts as **Pendiente** (Pending) and the order's value goes up by its price.
- If the order was **ready**, it goes back to **En proceso** (In progress).
- It only works on orders that are **In progress** or **Ready**. Garments cannot be added to a delivered or cancelled order.

<br clear="right">

## 9. Payments

<img src="capturas/14-registrar-pago.png" alt="Recording a payment" width="260" align="right">

**Recording a payment or deposit:** in the order details, tap **Registrar pago** (Record payment).

1. Type the **Valor** (Amount). If the customer pays everything, tap **Usar el saldo completo** (Use the full balance).
2. Choose the **Método** (Method): Efectivo (cash), Nequi or whichever your shop uses.
3. Tap **Guardar pago** (Save payment). The date is today's.

- A payment cannot be more than the balance.
- A delivered order can still receive payments, for example if the customer took the clothes while still owing. A cancelled order cannot.

<br clear="right">

<img src="capturas/15-anular-pago.png" alt="Voiding a payment" width="260" align="right">

**Voiding a payment recorded by mistake:** in the money section of the order details, tap **Anular este pago** (Void this payment) next to it.

- Type the **Motivo** (Reason); it is required.
- The system shows the resulting balance before you confirm.
- Tap **Anular pago** (Void payment). The payment is **not deleted**: it stops counting towards the balance and stays visible as voided, with the date and reason.

<br clear="right">

### How much you received and who owes you

<img src="capturas/22-dinero.png" alt="Money screen with the month's income and who owes money" width="260" align="right">

Tap **Dinero** (Money) in the bottom bar, or the **Por cobrar** (To collect) card on **Hoy**.

**Recibido** (Received): how much money came in. Choose **Hoy** (Today), **Semana** (Week, Monday to Sunday), **Mes** (Month) or **Fechas** (Dates). With **Fechas**, pick **Desde** (From) and **Hasta** (To) and tap **Ver lo recibido** (Show received).

- It adds up the payments and deposits of that period, by the day they were recorded.
- **Voided payments do not count.** If there were any, the system says so under the total, for example «5 pagos · no incluye 1 pago anulado» (5 payments, excluding 1 voided payment), so the figure matches what you remember.

**Quién me debe** (Who owes me): the total to collect and the orders that owe money, **from the largest debt to the smallest**, with their status. Tap one to open it and collect. Paid and cancelled orders do not appear.

<br clear="right">

## 10. Handing orders back

<img src="capturas/16-entregar-orden.png" alt="Handing back an order with a balance due" width="260" align="right">

When the customer comes for their clothes, open the order (find it by number) and tap **Entregar** (Hand back).

- The system shows **what is handed back**: the finished garments.
- Unfinished garments appear under **Se queda en el taller** (Stays in the shop). It is a partial delivery: the order stays In progress with what is left.
- **If the customer owes money,** the system says so before you confirm, for example «Ana debe $9.000. ¿Entregar de todos modos?» (Ana owes $9,000. Hand back anyway?). You can **Registrar un pago primero** (Record a payment first), hand back anyway with **Sí, entregar** (Yes, hand back), or **No entregar** (Do not hand back).

The **Entregar** button only appears when something is finished.

<br clear="right">

## 11. Notifying the customer on WhatsApp

**The notice goes out on its own.** When an order is ready, the system writes to the customer on WhatsApp from the shop's number:

> Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $21.000. Te esperamos.
> (Hi Marta, your order #0042 is ready for pick-up. Garments ready: 3. Balance due: $21,000. See you soon.)

In the order details, under **Avisos al cliente** (Customer notices), you see each notice and what happened to it: **Enviado** (Sent), **En cola** (Queued, goes out within minutes), **Por enviar** (To send) or **Descartado** (Discarded, the order stopped being ready before it was sent).

<img src="capturas/18-avisos.png" alt="Notices to send from your WhatsApp" width="260" align="right">

**If the notice could not go out on its own,** for example because WhatsApp did not respond after several tries, it stays **to send** and the **Hoy** screen shows it. Send it yourself:

1. On **Hoy**, tap **Avisos por enviar** (Notices to send).
2. On the customer's notice, tap **Abrir WhatsApp y enviar** (Open WhatsApp and send). WhatsApp opens in the customer's chat with the message already written.
3. Send it from WhatsApp and come back to the app.
4. Tap **Sí, ya lo envié** (Yes, I sent it). If you did not send it, do not tap it: the notice stays on the list.

If an order stops being ready, its notice leaves this list on its own.

<br clear="right">

## 12. Overdue and unclaimed orders

<img src="capturas/20-atrasadas.png" alt="Overdue orders" width="260" align="right">

**Overdue:** on **Hoy**, tap **Atrasadas** (Overdue). These are in-progress orders whose delivery date has passed, from most to least overdue, with how many days late each one is. Use it to decide what to work on first or whom to call to move the date.

<br clear="right">

<img src="capturas/21-sin-reclamar.png" alt="Unclaimed orders" width="260" align="right">

**Unclaimed:** on **Hoy**, tap **Sin reclamar** (Unclaimed). These are orders that have been ready for more than 30 days without anyone picking them up, with the customer's number and how much they owe. Message them to agree on a day. What to do with clothes that are never collected is up to the shop.

<br clear="right">

## 13. Cancelling an order

<img src="capturas/17-cancelar-orden.png" alt="Cancelling an order" width="260" align="right">

Only cancel an order if the customer **no longer wants the alteration**. In the order details, tap **Cancelar la orden** (Cancel the order), at the bottom, and confirm with **Sí, cancelar la orden** (Yes, cancel the order).

When it is cancelled:

- it no longer counts as pending work or as debt;
- deposits already paid stay recorded;
- it accepts no garments, payments or status changes;
- **it cannot be reopened**, and its number is not reused.

A delivered order cannot be cancelled.

<br clear="right">

## 14. No internet connection

<img src="capturas/19-sin-conexion.png" alt="No internet page" width="260" align="right">

The system keeps everything on the server, so it **needs internet** to save and look things up. Without a connection, the app shows: «Sin internet. El sistema necesita conexión para guardar y consultar tus órdenes» (No internet. The system needs a connection to save and look up your orders).

Check the Wi-Fi or mobile data and tap **Reintentar** (Try again). Nothing you already saved is lost.

<br clear="right">

## 15. Common problems

| What happens | What to do |
| --- | --- |
| **I forgot my password** | Ask the person who installed the system to set a new one, then change it in **Ajustes** (Settings) |
| **«Hiciste demasiados intentos»** (Too many attempts) | Wait the number of seconds shown and try again calmly |
| **The browser bar shows for a few seconds when the app opens** | Your main browser is not Chrome. It works the same; to open directly, set Chrome as the default ([section 2](#2-installing-the-app)) |
| **Android will not let me install the APK** | Allow installing apps from Chrome: in the warning, tap **Settings → Allow from this source** |
| **The customer says the notice never arrived** | In the order details, check **Avisos al cliente** (Customer notices). If it says **Por enviar** (To send), send it yourself from **Avisos por enviar**. If it says **Enviado** (Sent), check that the customer's number is correct on their record |
| **I entered the wrong price** | **Cambiar estado → Corregir arreglo, precio o fotos** ([section 8](#8-working-on-garments)) |
| **I recorded the wrong payment** | Void it with a reason ([section 9](#9-payments)). It is not deleted: it stays as voided |
| **A photo will not upload** | Check the connection. Each garment can have up to 3 photos and each photo can be up to 10 MB |
| **I marked a garment Finished by mistake** | Go back to **Cambiar estado** and mark it **En proceso** |
| **I forgot a garment in the order** | In the order details, **Agregar una prenda** (Add a garment) ([section 8](#8-working-on-garments)) |

## 16. Protecting your customers' data

Your customers' names and phone numbers are personal data. The system handles them under Colombia's data protection law, **Ley 1581 de 2012**, and you help protect them too:

- **Do not share your username or password.**
- **Sign out** if someone else is going to use the phone or computer.
- Garment photos **can only be seen while signed in**: they are not public and are not stored on the phone.
- Use each customer's number only to tell them about their clothes.
