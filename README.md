<img src="https://github.com/user-attachments/assets/5726fab7-e528-479a-8461-d2379acf50b3" width="600">


# 🛡️ pfSuriLogs 

Visor web para logs de Suricata en pfSense

**pfSuriLogs** es una herramienta ligera desarrollada en **PHP puro** para visualizar los logs de Suricata en **pfSense**, sin frameworks ni dependencias externas.

> 💡 Ideal para administradores de red que quieran un visor rápido, personalizable y seguro para múltiples interfaces.

---

## 🚀 Instalación rápida

1. **Copia los archivos a tu pfSense:**
   
   copia pfsurilogs.php y generarhash.php a /usr/local/www/
   

2. **Genera el hash de tu contraseña con:**
   
   visita la url de tu pfsense /generarhash.php y genera tu contraseña
   

3. **Edita `pfsurilogs.php` y configura tus credenciales de acceso:**

   Abre el archivo `pfsurilogs.php` y localiza el bloque de configuración de seguridad:

   ```php
   // --- ⚙️ 1. CONFIGURACIÓN DE SEGURIDAD ---
   define('USERNAME', 'admin'); 

   // Pega aquí el HASH que generaste con el script 'generarhash.php'.
   define('PASSWORD_HASH', 'AQUÍ_ENTRA_EL_HASH_GENERADO_SIN_ESPACIOS');
   ```

   > 🛡️ **Nota:** No pegues la contraseña en texto plano. Usa siempre el hash generado con `generarhash.php`.

---

4. **Configura las interfaces de red y las rutas de los logs:**

   En el mismo archivo, busca el siguiente bloque:

   ```php
   // --- A PARTIR DE AQUÍ, EL CÓDIGO COMPLETO DEL VISOR ---
   define('INTERFACES', [
       'wan1' => ['name' => 'WAN1 (pppoe164118)', 'path' => '/var/log/suricata/suricata_pppoe164118/'],
       'lan'  => ['name' => 'LAN (vtnet131562)',  'path' => '/var/log/suricata/suricata_vtnet131562/'],
       'wan2' => ['name' => 'WAN2 (vtnet244995)', 'path' => '/var/log/suricata/suricata_vtnet244995/'],
   ]);
   ```

   Cambia los nombres de las interfaces y las rutas según tu configuración en pfSense.

   > 📁 **Tip:** Puedes ver los nombres exactos de las interfaces y sus rutas en `/var/log/suricata/`. Asegúrate de usar los mismos nombres de carpeta que aparecen allí.


---

## 🔐 Seguridad

- Protección por usuario y contraseña (con hash bcrypt).
- Sin bases de datos ni sesiones innecesarias.
- Mismas restricciones que la web de administración de pfSense.

---

## 📂 Archivos incluidos

| Archivo             | Descripción                                                |
|---------------------|------------------------------------------------------------|
| `pfsurilogs.php`     | Visor principal de logs con autenticación.                 |
| `generarhash.php`    | Script para generar hashes bcrypt para contraseñas seguras.|

---

## 🧰 Requisitos

- pfSense con Suricata.
- Acceso al sistema de archivos (`/usr/local/www`).
- PHP 7.x u 8.x (el que venga con pfSense).

---

## ☕ Donaciones

Si esta herramienta te ha sido útil, puedes **invitarme a un café**:

[![Invítame un café](https://img.shields.io/badge/Invítame_un_café-FF813F?style=for-the-badge&logo=buy-me-a-coffee&logoColor=white)](https://www.paypal.me/SinCracK)

---

## 📄 Licencia

Este proyecto está licenciado bajo la licencia [MIT](LICENSE).

Desarrollado por **SinCracK**.
