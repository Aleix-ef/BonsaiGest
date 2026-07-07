# BonsaiGest Desktop

Aplicación local de escritorio para gestionar una colección personal de bonsáis con Tauri.

Los datos se guardan en el propio ordenador mediante IndexedDB. No usa servidor, Laravel ni internet para funcionar.

## Desarrollo

```bash
cd desktop
npm install
npm run dev
```

## Ejecutar con Tauri

Requiere Rust y las dependencias de Tauri instaladas en el sistema.

En Windows para compilar la app:

1. Instala Node.js LTS: https://nodejs.org/
2. Instala Rust con rustup: https://rustup.rs/
3. Instala Microsoft C++ Build Tools: https://visualstudio.microsoft.com/visual-cpp-build-tools/
4. En el instalador de Build Tools marca "Desktop development with C++".
5. Reinicia la terminal.

Windows 10/11 normalmente ya trae WebView2 instalado. Si falta, instálalo desde Microsoft:
https://developer.microsoft.com/microsoft-edge/webview2/

En Linux Mint/Ubuntu:

```bash
sudo apt update
sudo apt install -y libwebkit2gtk-4.1-dev build-essential curl wget file libxdo-dev libssl-dev libayatana-appindicator3-dev librsvg2-dev
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
```

Cierra y vuelve a abrir la terminal después de instalar Rust.

```bash
cd desktop
npm run tauri:dev
```

## Crear instalador

```bash
cd desktop
npm run tauri:build
```

En Windows generará instaladores `.exe`/`.msi` dentro de:

```bash
desktop/src-tauri/target/release/bundle/
```

Para instalar una versión nueva en otro PC, ejecuta el instalador nuevo encima del anterior. Antes de actualizar, conviene exportar una copia JSON desde la app.

## Copias de seguridad

La app incluye exportación e importación JSON desde la interfaz para guardar una copia de la colección.
