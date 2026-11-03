# cqpweb-new-ui

VERSIÓN 1.0.2

Una interfaz moderna, envío de correo que funciona y una serie de correcciones para [CQPweb](https://cwb.sourceforge.io/cqpweb.php) 3.3.19, la interfaz web del IMS Open Corpus Workbench. Se instala sobre un CQPweb existente con una sola orden, se guarda una copia de respaldo de todo lo que se reemplaza y se puede desinstalar de la misma manera.

Autor: Scott Sadowsky.

La interfaz está en inglés. La versión en inglés de este archivo es [README.md](README.md).

## Qué se agrega

### Apariencia

- Se creó un nuevo diseño para todas las páginas de CQPweb: una barra superior con los botones "Homepage" y "Back to *corpus*", tablas y formularios más claros y páginas que funcionan en teléfonos.
- Se agregó un modo diurno y uno nocturno, que cada visitante elige con el botón de la esquina superior derecha.
- Los colores se eligen en una nueva página de administración, **Appearance**: doce esquemas de color, por separado para la página de inicio, las páginas de usuario, las de administración y cada corpus, con cada color ajustable y una verificación de contraste en vivo.
- Las fuentes se sirven desde el propio servidor: se incluyen siete familias y se pueden agregar unas 1.600 familias de Google Fonts desde la página Appearance. Los navegadores de los visitantes nunca se conectan con Google.
- Se creó una nueva página de inicio con una tarjeta por corpus, con el color de ese corpus, su tamaño y enlaces a su página de búsqueda y a su documentación.
- En las concordancias se puede destacar una de cada dos palabras con un fondo, con un color de texto o con ambos (palabra y etiqueta juntas cuando las etiquetas se muestran debajo). El valor por defecto se fija por corpus y cada visitante lo puede cambiar sobre la concordancia.
- Se aclaró la línea de resumen de las concordancias: la búsqueda en tipografía de código, el tamaño del corpus y la frecuencia por millón de palabras.

### Personalización (nueva página de administración, en "Frontend interface")

- Nombre del sitio, nombre y dirección web del responsable y dirección de contacto.
- Logo e ícono del navegador propios, que se suben desde la página. Los íconos que usan los teléfonos cuando se guarda el sitio en la pantalla de inicio se generan a partir del ícono del navegador.
- El texto de bienvenida de la página de inicio, un texto al pie de cada página, el aviso de la página "Create an account" y el título de la página de novedades.
- Los valores por defecto del formulario de búsqueda: Simple query o CQP syntax, y con o sin distinción de mayúsculas.
- Páginas públicas de documentación para cada corpus, que se pueden leer sin iniciar sesión.

Lo que no se configura se toma del archivo de configuración del propio CQPweb (su logo y su mensaje de bienvenida, la dirección del administrador). Solo cuando ahí tampoco hay nada se muestran los textos de relleno: una imagen "Your Logo Here!" y "Welcome to [YOUR SITE NAME HERE]! Created by [YOUR NAME HERE]".

### Correo que llega (nueva página de administración, la primera de "Backend system")

Tal como viene, CQPweb casi nunca logra entregar los mensajes de confirmación de cuenta ni los de cambio de contraseña: los entrega al sistema de correo local del servidor, las empresas de alojamiento bloquean el puerto que se necesita y los proveedores de correo descartan los mensajes de servidores que no son remitentes reconocidos. Con este paquete, CQPweb inicia sesión en un servidor de correo saliente de verdad (un servicio de retransmisión SMTP comercial o el servidor de la propia institución) y envía a través de él. No se necesita instalar nada más en el servidor.

La página **E-mail** contiene:

- el servidor, el puerto, el cifrado, el usuario y la contraseña (que se guarda en un archivo que solo el servidor web puede leer y que nunca se vuelve a mostrar);
- la dirección y el nombre del remitente, y una dirección de respuesta;
- un botón "Send a test message" que muestra toda la conversación con el servidor de correo, con la contraseña oculta;
- los registros SPF, DMARC y MX del dominio, tal como los ve el servidor;
- una guía paso a paso de toda la cadena: la elección de la dirección del remitente, la cuenta en el servicio de retransmisión, la verificación del dominio (DKIM y SPF), el registro DMARC, las pruebas y el significado de cada error.

Mientras el envío por SMTP está desactivado, CQPweb envía el correo igual que sin el paquete.

### Página de ayuda

Se agregó una página pública **Help**, enlazada al pie de todas las páginas, con:

1. el manual de la interfaz y del lenguaje de búsqueda CQP, de los autores de CQP;
2. un tutorial de sintaxis CQP en español;
3. un tutorial de sintaxis CQP en inglés;
4. los videotutoriales del proyecto CWB;
5. el manual de administración de CQPweb;
6. la página de descarga e instalación de CWB;
7. el archivo de la lista de correo de CWB.

En la página Appearance, cada entrada se puede mostrar u ocultar, se pueden cambiar su título, su descripción y su dirección, y se pueden agregar entradas nuevas (título, descripción y dirección).

Los dos tutoriales aparecen además en el menú de cada corpus, en "Tutorials", y un recuadro que los enlaza aparece sobre el campo de búsqueda cuando se elige "CQP syntax".

### Búsquedas

- Se agregó una casilla "Case-sensitive" en todos los formularios de búsqueda, tanto en Simple query como en CQP syntax. Su estado inicial es el valor por defecto del sitio y cada navegador recuerda la elección del visitante. En CQP syntax, `%C` después de un valor hace que solo ese valor distinga mayúsculas.
- Se unieron los dos modos de búsqueda simple en una sola entrada, "Simple query", al principio de la lista; la casilla decide si se distinguen mayúsculas.
- Las palabras escritas con "CQP syntax" seleccionado pero sin los corchetes de CQP (`house`, `my house`), que CQP rechazaría, se buscan como `[word="house"]`, con un aviso claro.
- Se desactiva "Match strategy", con una explicación, mientras está seleccionado "CQP syntax" (CQPweb lo ignora en ese modo).
- Cuando todos los espacios de base de datos están ocupados, una página de espera vuelve a intentar sola cada cinco segundos, en lugar del error "Too many database processes!" de CQPweb.

### Cuentas

- Se agregó una regla para las contraseñas: al menos 12 caracteres (o el mínimo de CQPweb, si es mayor), con una minúscula, una mayúscula, un dígito y un carácter especial. Se verifica en el servidor y los formularios muestran una lista de comprobación en vivo.
- **Maintenance mode** (nueva página de administración): mientras está activado, solo los superusuarios pueden usar el sitio; los demás ven el mensaje que se haya escrito.

### Páginas y mensajes

- Los mensajes del sistema se pueden editar (título, fecha y texto), no solo agregar y borrar; junto a cada mensaje aparece un enlace "[edit]" para los administradores y "[x]" lo borra en el mismo lugar.
- **News page**: una página pública con las novedades del sitio, que se escribe en una página de administración con un formato de texto sencillo.
- **Edit user pages**: las páginas HTML de la carpeta de páginas de usuario (tutoriales, documentación) se crean y se editan desde el panel de administración, y se guardan las versiones anteriores.
- Las páginas incrustadas se muestran dentro del diseño del sitio. CQPweb 3.3.19 solo mostraba la ruta del archivo.

### Correcciones a CQPweb 3.3.19

- Se corrigió que los navegadores siguieran usando copias antiguas de los scripts de CQPweb después de una actualización (el indicador de espera pedía `undefinedcss/img/throbber.gif` y la página de búsqueda se detenía con "grab is not defined"). Ahora cada script y cada hoja de estilo se piden con una etiqueta de versión.
- Se corrigió que el generador de páginas (`UiPageFramer`) pidiera `jquery.js` y `always.js` sin su carpeta `jsc/` y que nunca imprimiera sus hojas de estilo.
- Se corrigió que, con todos los espacios de base de datos ocupados, quedara un registro de base de datos a medio hacer que hacía fallar también el reintento.
- Se corrigió que las páginas incrustadas mostraran la ruta del archivo en lugar de su contenido.
- Se eliminó la etiqueta "location" duplicada del formulario de registro.
- Se corrigió la verificación de contraseñas del propio CQPweb en el formulario de registro (`jsc/useracct-create.js`), que usaba una variable no definida, se detenía con un error y dejaba pasar cualquier contraseña. Ahora funciona, y además el servidor verifica la regla de contraseñas.
- El enlace de ayuda de la sintaxis CQP del formulario de búsqueda apunta ahora al manual actual de la interfaz y del lenguaje CQP (PDF).
- Todos los enlaces a otros sitios usan https (el archivo de la lista de correo de CWB conserva la dirección http con que se publica).
- Se adoptó la ortografía estadounidense en toda la interfaz ("Analyze corpus", "color scheme", "Categorized queries").

## Requisitos

- CQPweb 3.3.19 (revisión 1918 de Subversion). El instalador lo verifica.
- PHP 8 con las extensiones `intl` y `mbstring`. La extensión `gd` es opcional: sin ella, los íconos para la pantalla de inicio de los teléfonos no se generan a partir del ícono subido.
- Python 3 para ejecutar el instalador.
- Linux (CQPweb solo funciona en sistemas de tipo Unix).
- Para agregar fuentes desde la página Appearance, el servidor debe poder conectarse con fonts.googleapis.com y fonts.gstatic.com.

CQPweb 3.3.19 se obtiene con Subversion:

```bash
svn checkout -r 1918 https://svn.code.sf.net/p/cwb/code/gui/cqpweb/trunk cqpweb
```

## Instalación

1. Se descarga este repositorio (en GitHub: Code > Download ZIP), se descomprime en el servidor y se abre una terminal en la carpeta descomprimida (`CQPweb-New-UI-main` si el ZIP viene de GitHub).
2. Se revisa lo que haría el instalador, sin cambiar nada:

   ```bash
   sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --dry-run
   ```

3. Se instala:

   ```bash
   sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb
   ```

4. Se inicia sesión en CQPweb como administrador y se abre el panel de administración:
   - **Frontend interface > Personalization**: nombre, logo, ícono y textos del sitio;
   - **Backend system > E-mail**: se sigue la guía de la página;
   - **Frontend interface > Appearance**: colores, fuentes y enlaces de la página de ayuda.

Las opciones y la salida del instalador se describen en [cqpweb-new-ui-install_README.md](cqpweb-new-ui-install_README.md).

No hace falta cambiar la configuración del servidor web: el paquete sirve sus propias fuentes, imágenes y páginas. También funciona detrás de un cortafuegos de aplicaciones web como ModSecurity con las reglas OWASP: las páginas de administración envían lo que se escribe en ellas codificado en base64, para que el cortafuegos no bloquee el guardado. Sin eso, las reglas OWASP bloquean, por ejemplo, una contraseña de correo con comillas como las de una orden SQL, un texto que menciona una orden como `cat /etc/passwd` y el HTML pegado con un script o un atributo de evento; los enlaces y el formato simples pasan de todos modos.

## Carpetas y permisos

Todo lo que escribe el paquete se guarda fuera de la carpeta de CQPweb, en una carpeta de datos que pertenece al usuario del servidor web (por defecto `/usr/local/share/cqpweb/cqpweb-new-ui`):

| Ruta dentro de la carpeta de datos | Contenido |
|---|---|
| `appearance.json` | colores y fuentes (página Appearance) |
| `brand.json` | ajustes de la página Personalization |
| `help.json` | enlaces de la página de ayuda |
| `site.json` | modo de mantenimiento |
| `mail.json` | ajustes de correo, con la contraseña; permisos 600 |
| `brand/` | el logo y los íconos subidos |
| `fonts/` | las fuentes y el archivo generado `cp-fonts.css` |
| `pages/` | las páginas de usuario: tutoriales, documentación, la página de novedades |
| `news-source.txt`, `news-history/`, `page-history/` | el texto de la página de novedades y las versiones anteriores de las páginas |

Con las opciones `--data-dir`, `--font-dir`, `--pages-dir`, `--font-url` y `--pages-url` del instalador, estas carpetas se pueden ubicar en otro lugar (por ejemplo, donde un sitio ya guarda sus fuentes y páginas); el instalador las anota entonces en `lib/cqpweb-new-ui/local-config.php`.

Las imágenes y los PDF que usan las páginas de usuario se copian a mano en la carpeta de páginas y se citan solo por su nombre de archivo.

## Qué no hace

- Nunca se modifica la base de datos de CQPweb ni su archivo de configuración.
- Nunca se modifica la configuración del servidor web ni el sistema de correo del servidor.
- No se hace nada con cortafuegos, fail2ban ni ningún otro software de protección. Su configuración queda a cargo de los administradores de cada sitio.

## Actualización

Se ejecuta el instalador de la nueva versión de la misma manera. Los archivos que siguen siendo los originales de CQPweb, o las copias de esta versión, se tratan automáticamente; los que difieren de ambos se listan y el instalador se detiene hasta que se vuelva a ejecutar con `--accept-modified`. Cada ejecución hace una nueva copia de respaldo.

## Desinstalación

```bash
sudo python3 cqpweb-new-ui-install.py --cqpweb-root /var/www/html/cqpweb --uninstall
```

Se restauran los archivos originales de CQPweb desde la primera copia de respaldo que hizo el instalador y se eliminan los archivos que agregó el paquete. La carpeta de datos se conserva; se borra a mano si ya no se necesita.

## Archivos de CQPweb que se modifican

Se reemplazan 14 archivos de CQPweb; cada cambio está marcado con `cqpweb-new-ui` en el código y las diferencias están en `patches/`. `core-originals.sha256` contiene las sumas de verificación de los originales. La lista detallada de cambios por archivo está en [README.md](README.md#files-changed-in-cqpweb).

## Licencias

- cqpweb-new-ui es software libre bajo la Licencia Pública General de GNU, versión 2 ([LICENSE](LICENSE)), la misma licencia de CQPweb. Los archivos de CQPweb que incluye son de CQPweb, de Andrew Hardie y el equipo de desarrollo de CWB, con los cambios ya descritos.
- Las fuentes de `fonts/google/` están bajo la SIL Open Font License 1.1; la carpeta de cada familia contiene su `OFL.txt` con su aviso de derechos de autor.
- `css/cqpweb-new-ui/google-fonts.json` contiene los nombres y las categorías de las familias de Google Fonts, tomados de los metadatos públicos de Google Fonts.

## Historial de cambios

- 1.0.2: se probó en un servidor CQPweb 3.3.19 con MySQL 8.0, Apache 2.4 y ModSecurity con las reglas OWASP. Se corrigió que, al guardar los enlaces de la página de ayuda con una entrada ausente de la solicitud, esa entrada quedara oculta; se corrigió el script de verificación de contraseñas del registro de CQPweb (`jsc/useracct-create.js`), que ya no se detiene con un error; se centraron los enlaces del pie de cada página (CQPweb les daba el ancho de su menú lateral); los campos de dirección web tienen el mismo estilo que los demás; el recuadro del mensaje de prueba ya no propone la dirección provisional que el script de instalación de CQPweb asigna a los superusuarios; el instalador se ejecuta con `python3`, ya que una descarga desde GitHub no conserva su permiso de ejecución.
- 1.0.1: las páginas Personalization, E-mail, Maintenance mode y System messages, y la sección de la página de ayuda en Appearance, envían sus campos codificados en base64, para que un cortafuegos de aplicaciones web no bloquee el guardado (ModSecurity con las reglas OWASP interpreta como ataque los enlaces, etiquetas y contraseñas de una solicitud).
- 1.0.0: se publicó la primera versión.
