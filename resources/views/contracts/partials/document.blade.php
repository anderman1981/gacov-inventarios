@php
    $signatureImageSrc = $signatureImageSrc ?? ($contract->client_signature_path ? Storage::disk('public')->url($contract->client_signature_path) : null);
@endphp

@if(!($isPdf ?? false))
<style>
.contract-paper {
    max-width: 980px;
    margin: 0 auto;
    padding: 22px;
    background: #fff;
    color: #111827;
}

.contract-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 18px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e5e7eb;
}

.contract-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(239, 68, 68, .08);
    color: #b91c1c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.contract-header h1 {
    margin: 10px 0 6px;
    font-size: clamp(22px, 3vw, 34px);
    line-height: 1.1;
}

.contract-subtitle {
    color: #6b7280;
    font-size: 12px;
}

.contract-status {
    min-width: 180px;
    text-align: right;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #6b7280;
}

.contract-status.is-signed { color: #166534; }
.contract-status.is-pending { color: #b45309; }

.contract-section {
    margin-top: 18px;
}

.contract-section h2 {
    margin: 0 0 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.contract-section p,
.contract-section li {
    font-size: 13px;
    line-height: 1.7;
    color: #1f2937;
}

.contract-note {
    color: #4b5563;
    font-style: italic;
}

.parties-grid,
.contract-columns,
.signatures-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.party-card,
.contract-columns > div,
.signature-card {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 14px;
    background: #fafafa;
}

.party-card h3 {
    margin: 0 0 8px;
    font-size: 12px;
    text-transform: uppercase;
}

.party-card p,
.signature-card p {
    margin: 0 0 4px;
}

.contract-columns ul {
    margin: 0;
    padding-left: 18px;
}

.contract-columns li {
    margin-bottom: 6px;
}

.contract-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}

.contract-table td {
    border: 1px solid #e5e7eb;
    padding: 9px 10px;
    font-size: 13px;
}

.contract-table td:first-child {
    width: 42%;
    background: #f9fafb;
    font-weight: 700;
}

.signature-line {
    min-height: 88px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    border-bottom: 1px solid #111827;
    margin-bottom: 10px;
}

.signature-line img {
    max-width: 100%;
    max-height: 66px;
}

.provider-line {
    color: #b91c1c;
    font-weight: 800;
    font-size: 16px;
}

.signature-name {
    font-weight: 800;
}

.signature-meta {
    color: #6b7280;
    font-size: 12px;
}

.contract-footer {
    margin-top: 16px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
    font-size: 12px;
    color: #6b7280;
}

.contract-alert {
    margin: 18px 0;
    padding: 16px 18px;
    border-radius: 16px;
    border: 1px solid #dbe3ee;
    background: #f8fafc;
    color: #111827;
}

.contract-alert h3 {
    margin: 0 0 8px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.contract-alert.info {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.contract-alert.warning {
    background: #fffbeb;
    border-color: #fcd34d;
}

.contract-alert.success {
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.highlight-row td {
    background: #f0fdf4 !important;
    font-weight: 800;
}

.sla-table th {
    background: #374151;
    color: #fff;
}

@media (max-width: 720px) {
    .contract-header {
        flex-direction: column;
    }

    .parties-grid,
    .contract-columns,
    .signatures-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endif

<article class="contract-paper">
    <header class="contract-header">
        <div>
            <div class="contract-badge">AMR Tech · Inversiones GACOV</div>
            <h1>Contrato Base de Desarrollo, Implementación y Soporte Temporal</h1>
            <p class="contract-subtitle">Contrato No. {{ $contract->contract_number }} · Medellín, Antioquia · {{ $contract->contract_date->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>
        </div>
        @if(!($isPdf ?? false))
        <div class="contract-status {{ $contract->status === 'signed' ? 'is-signed' : 'is-pending' }}">
            {{ $contract->status_label }}
        </div>
        @endif
    </header>

    <section class="contract-section">
        <h2>Partes</h2>
        <div class="parties-grid">
            <div class="party-card">
                <h3>Prestador</h3>
                <p><strong>{{ $contract->provider_name }}</strong></p>
                @if($contract->provider_document)<p>Documento: {{ $contract->provider_document }}</p>@endif
                @if($contract->provider_email)<p>Correo: {{ $contract->provider_email }}</p>@endif
                @if($contract->provider_phone)<p>Tel: {{ $contract->provider_phone }}</p>@endif
                @if($contract->provider_address)<p>Dirección: {{ $contract->provider_address }}</p>@endif
            </div>
            <div class="party-card">
                <h3>Cliente</h3>
                <p><strong>{{ $contract->client_company_name }}</strong></p>
                <p>NIT: {{ $contract->client_document }}</p>
                <p>Representante: {{ $contract->client_legal_representative }}</p>
                @if($contract->client_legal_representative_document)<p>C.C.: {{ $contract->client_legal_representative_document }}</p>@endif
                @if($contract->client_email)<p>Correo: {{ $contract->client_email }}</p>@endif
                @if($contract->client_phone)<p>Tel: {{ $contract->client_phone }}</p>@endif
                @if($contract->client_address)<p>Dirección: {{ $contract->client_address }}</p>@endif
            </div>
        </div>
    </section>

    <section class="contract-section">
        <h2>Objeto</h2>
        <p>
            El Prestador se obliga a desarrollar e implementar para el Cliente un sistema web a la medida en Laravel 13
            para gestión de inventario, surtido de máquinas vending, operación logística y administración, con ejecución
            progresiva por fases conforme al alcance acordado entre las partes.
        </p>
        <p class="contract-note">
            El contrato podrá operar temporalmente en infraestructura del Prestador mientras el Cliente habilita su entorno
            definitivo. Durante ese periodo aplicará la mensualidad operativa de la fase activa.
        </p>
        @if($contract->summary)
        <p><strong>Resumen comercial:</strong> {{ $contract->summary }}</p>
        @endif
    </section>

    <section class="contract-section">
        <h2>Cláusula Tercera — Estructura de Precios y Forma de Pago</h2>
        <p><strong>3.1 Valor de arranque para la Fase 1:</strong></p>
        <table class="contract-table table-wide">
            <thead>
                <tr><td>Pago</td><td>Monto</td><td>Fecha</td></tr>
            </thead>
            <tbody>
                <tr><td>Pago inicial (50%)</td><td class="price-col">$1.900.000</td><td>16 de abril de 2026</td></tr>
                <tr><td>Pago restante (50%)</td><td class="price-col">$1.900.000</td><td>Al entregar Fase 1 (30 abril 2026, sujeto a pruebas y aprobaciones)</td></tr>
                <tr><td>Mensualidad operativa Fase 1 activa</td><td class="price-col">$290.000/mes</td><td>Mínimo 3 meses mientras opere en infraestructura del Prestador</td></tr>
                <tr class="highlight-row"><td><strong>Ciclo mínimo Fase 1</strong></td><td class="price-col">$4.670.000</td><td>Desarrollo + 3 meses de operación temporal</td></tr>
            </tbody>
        </table>

        <p><strong>3.2 Fases activas, mensualidad operativa y mínimo para upgrade:</strong></p>
        <table class="contract-table table-wide">
            <thead>
                <tr><td>Fase</td><td>Plan</td><td>Alcance</td><td class="price-col">Mensualidad operativa</td><td>Mínimo</td><td>Regla comercial</td></tr>
            </thead>
            <tbody>
                <tr><td>Fase 1 – Arranque</td><td>Starter</td><td>Operación base, inventario, bodegas, máquinas, rutas y movilidad inicial</td><td class="price-col">$290.000/mes</td><td>3 meses</td><td>Inicia con inversión de $3.800.000 dividida 50/50.</td></tr>
                <tr><td>Fase 2 – Control Comercial</td><td>Básico</td><td>Ventas por máquina, reportes base, gestión multi-ruta y lectura comercial de la operación</td><td class="price-col">$690.000/mes</td><td>3 meses</td><td>Upgrade o arranque directo mediante nueva propuesta o anexo.</td></tr>
                <tr><td>Fase 3 – Profesional</td><td>Profesional</td><td>Analítica, alertas, filtros avanzados y trazabilidad gerencial</td><td class="price-col">$890.000/mes</td><td>3 meses</td><td>Upgrade o arranque directo mediante nueva propuesta o anexo.</td></tr>
                <tr><td>Fase 4 – Integraciones</td><td>Empresarial</td><td>WorldOffice, geolocalización, API externa y conectores aprobados</td><td class="price-col">$1.200.000/mes</td><td>3 meses</td><td>Upgrade o arranque directo mediante nueva propuesta o anexo.</td></tr>
                <tr class="highlight-row"><td>Fase 5 – Enterprise</td><td>Enterprise</td><td>White-label, madurez multiempresa y cierre premium de escalamiento</td><td class="price-col">$1.500.000/mes</td><td>3 meses</td><td>Se formaliza por nueva propuesta con valores vigentes al momento.</td></tr>
            </tbody>
        </table>

        <p><strong>3.3 Costos operacionales, garantía y soporte posterior:</strong></p>
        <table class="contract-table table-wide">
            <thead>
                <tr><td>Concepto</td><td>Descripción</td><td class="price-col">Valor</td></tr>
            </thead>
            <tbody>
                <tr><td>Garantía correctiva inicial</td><td>Corrección de errores propios del desarrollo durante la garantía de la fase entregada</td><td class="price-col">Incluida por 30 días</td></tr>
                <tr><td>Hosting temporal, dominio del proveedor y operación</td><td>Se encuentran cubiertos dentro de la mensualidad operativa de la fase activa mientras el sistema esté en infraestructura del Prestador</td><td class="price-col">Según fase vigente</td></tr>
                <tr><td>Bolsa de horas programada</td><td>Ajustes, mejoras menores o soporte funcional fuera de garantía</td><td class="price-col">$120.000/hora</td></tr>
                <tr><td>Hora urgente / prioritaria</td><td>Atención priorizada fuera de garantía</td><td class="price-col">$150.000/hora</td></tr>
                <tr class="highlight-row"><td><strong>Valor comercial total de referencia</strong></td><td><strong>Ruta completa Fase 1 → Fase 5 para la opción Laravel 13 a la medida</strong></td><td class="price-col">$18.144.000</td></tr>
            </tbody>
        </table>

        <div class="contract-alert info">
            <h3>📌 Nota sobre modularidad, abonos y tope comercial</h3>
            La Fase 1 se entrega con un núcleo operativo funcional y con arquitectura lista para crecer por etapas sin rehacer el Sistema.
            Cada fase posterior se formaliza mediante anexo escrito y <strong>todo pago o upgrade aprobado se abona al valor total del proyecto</strong>.
            Cada fase activa tiene un mínimo de <strong>3 meses</strong>. Si al terminar ese ciclo no hay nueva inversión, el Cliente podrá continuar
            en la fase vigente con la misma mensualidad operativa. El desarrollo contratado no podrá superar <strong>$18.144.000</strong>, salvo que
            el Cliente solicite por escrito un alcance nuevo o adicional.
        </div>

        <p><strong>3.4 Forma de pago y facturación:</strong> Transferencia bancaria o PSE a la cuenta indicada por el Prestador. Los pagos de desarrollo se realizarán conforme a cada fase aprobada. La mensualidad operativa de la fase activa solo se facturará mientras el Sistema opere en infraestructura del Prestador y mientras dicha fase permanezca activa. No existe un mantenimiento fijo adicional obligatorio por fuera de esa mensualidad temporal. <strong>Cada pago aprobado, mensualidad operativa, hora adicional o cobro extraordinario deberá generar su factura correspondiente</strong>, la cual servirá como soporte formal del avance económico del proyecto.</p>
        <table class="contract-table">
            <thead>
                <tr><td colspan="2">Datos de consignación</td></tr>
            </thead>
            <tbody>
                <tr><td>Beneficiario</td><td>{{ $contract->bank_account_holder }}</td></tr>
                <tr><td>Banco</td><td>{{ $contract->bank_name }}</td></tr>
                <tr><td>Tipo de cuenta</td><td>{{ $contract->bank_account_type }}</td></tr>
                <tr><td>Número de cuenta</td><td>{{ $contract->bank_account_number }}</td></tr>
            </tbody>
        </table>
        <p><strong>3.5 Garantía correctiva:</strong> Cada fase entregada tendrá una garantía correctiva de <strong>30 días calendario</strong> contados desde su aceptación. Durante ese periodo, los errores propios del desarrollo se corregirán sin cobro adicional. Ajustes evolutivos, nuevos requerimientos o cambios de alcance no hacen parte de la garantía.</p>
        <p><strong>3.6 Permanencia mínima, continuidad y nuevas propuestas:</strong> Cada fase activa tendrá una permanencia mínima de <strong>3 meses</strong> antes de evaluar upgrade. Si el Cliente decide no hacer inversión adicional al terminar ese periodo, podrá continuar en la misma fase con la mensualidad vigente. <strong>Con al menos 15 días calendario antes de cumplirse el ciclo vigente</strong>, las partes revisarán si habrá continuidad, escalamiento o ajuste de alcance, con el fin de reorganizar la propuesta siguiente y el plan de pagos. Si el Cliente desea arrancar en una fase superior, subir de fase o aprobar un monto diferente al aquí descrito, el Prestador emitirá una <strong>nueva propuesta o anexo comercial</strong> con entregables, tiempos, valor de activación y mensualidad ajustados al monto aprobado y a los pagos previamente abonados.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Cuarta — Propiedad Intelectual, Uso y Datos</h2>
        <p><strong>4.1</strong> La <strong>información operativa, inventarios, reportes, usuarios, rutas, datos comerciales y bases de datos del Cliente</strong> son de propiedad del Cliente.</p>
        <p><strong>4.2</strong> Los <strong>componentes reutilizables, librerías internas, metodologías, utilidades genéricas, herramientas OCR, know-how técnico y demás activos preexistentes del Prestador</strong> continúan siendo propiedad de AMR Tech.</p>
        <p><strong>4.3</strong> Una vez el Cliente pague las fases contratadas y se entregue la versión correspondiente para su operación, podrá usar el Sistema para su actividad interna, ya sea en infraestructura temporal del Prestador o en su propia infraestructura cuando se complete la migración.</p>
        <p><strong>4.4</strong> El Cliente no podrá revender, sublicenciar ni comercializar el Sistema a terceros sin acuerdo escrito independiente.</p>
    </section>

    <section class="contract-section">
        <div class="contract-alert warning">
            <h3>⚠️ Claridad sobre propiedad y migración</h3>
            Este contrato <strong>no establece un fee fijo de transferencia tecnológica</strong>. La migración de la versión contratada al servidor del Cliente se coordinará conforme a la Cláusula Quinta. Solo podrán cotizarse aparte labores extraordinarias de infraestructura, hardening o cumplimiento que excedan la migración base.
        </div>
    </section>

    <section class="contract-section">
        <h2>Cláusula Quinta — Migración a Servidor Propio</h2>
        <p><strong>5.1</strong> El Cliente podrá solicitar la migración del Sistema a su propio servidor una vez haya pagado las fases efectivamente contratadas y suministre un ambiente compatible, accesos, ventanas de despliegue y lineamientos de seguridad suficientes para la ejecución técnica.</p>
        <p><strong>5.2</strong> La migración base de la versión contratada se entiende incluida dentro del marco comercial del proyecto. No existe un fee fijo de transferencia. Sin embargo, si el Cliente exige labores extraordinarias no previstas, tales como arquitectura cloud especializada, VPN, whitelisting complejo, endurecimiento adicional, cumplimiento corporativo específico o intervención intensiva de terceros, dichas actividades podrán cotizarse por aparte mediante anexo.</p>
        <p><strong>5.3</strong> Una vez el Sistema opere en infraestructura del Cliente, el cobro de la <strong>mensualidad operativa de la fase activa</strong> cesará. El soporte posterior podrá contratarse únicamente por bolsa de horas, si el Cliente así lo decide.</p>
        <p><strong>5.4</strong> Mientras la migración no se ejecute o no sea técnicamente viable por causas imputables al ambiente del Cliente, el Sistema podrá continuar operando temporalmente en infraestructura del Prestador bajo la mensualidad operativa vigente de la fase activa.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Sexta — Obligaciones del Cliente</h2>
        <ul>
            <li>Entregar el inventario inicial (Bodega Principal, Vehículos y Máquinas) en <strong>formato digital (Excel preferido)</strong> antes de la fecha indicada en el cronograma. Si los datos se entregan en papel, imágenes sin estructura u otros formatos no digitales, el Prestador podrá: (a) cobrar un cargo único de digitalización de <strong>$450.000</strong>; o (b) proponer la activación del módulo OCR como servicio o add-on separado.</li>
            <li>Designar un interlocutor técnico interno responsable de coordinar pruebas y validación.</li>
            <li>Garantizar conectividad adecuada para los conductores durante la fase de pruebas.</li>
            <li>Notificar al Prestador con al menos 15 días calendario antes de cumplirse el ciclo vigente de 3 meses si desea reorganizar la propuesta, ajustar el plan de pagos o escalar a una fase superior.</li>
            <li>Mantener al día los pagos de las fases aprobadas y, cuando aplique, la mensualidad operativa de la fase activa o los servicios post-garantía expresamente solicitados.</li>
        </ul>
    </section>

    <section class="contract-section">
        <h2>Cláusula Séptima — OCR, IA y Servicios de Terceros</h2>
        <p><strong>7.1</strong> El <strong>módulo OCR / captura inteligente</strong> no hace parte automática de la Fase 1 ni del valor base inicial del Sistema. Durante el onboarding inicial, el Prestador podrá usar herramientas propias de OCR o IA para acelerar migraciones o digitalizaciones, sin que ello implique licenciamiento permanente al Cliente.</p>
        <p><strong>7.2</strong> Si el Cliente desea habilitar el OCR como módulo operativo dentro del Sistema, deberá suscribirse un <strong>anexo comercial y técnico</strong>, con valores definidos en la propuesta vigente al momento de su aprobación, más los costos variables de infraestructura o consumo de IA que resulten aplicables.</p>
        <p><strong>7.3</strong> Cuando el OCR se opere con APIs de terceros o modelos externos, el excedente de consumo o cambios tarifarios podrán facturarse al <strong>costo real del proveedor más margen operativo razonable</strong>, previo aviso al Cliente.</p>
        <p><strong>7.4</strong> Cambios en las tarifas de terceros (Google, APIs de mapas, hosting, correo, etc.) que impacten el costo operativo podrán ajustarse en el costo operacional temporal o en el módulo correspondiente con previo aviso de 30 días.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Octava — Nivel de Servicio (SLA)</h2>
        <table class="contract-table table-wide sla-table">
            <thead>
                <tr><td>Indicador</td><td>Compromiso</td></tr>
            </thead>
            <tbody>
                <tr><td>Disponibilidad mensual del sistema</td><td>99% mientras opere en infraestructura del Prestador (≤ 7,2 h/mes de mantenimiento programado)</td></tr>
                <tr><td>Tiempo de respuesta — Error crítico (sistema caído)</td><td>Máximo 4 horas hábiles</td></tr>
                <tr><td>Tiempo de respuesta — Error funcional (módulo falla)</td><td>Máximo 24 horas hábiles</td></tr>
                <tr><td>Tiempo de respuesta — Mejora o ajuste de configuración</td><td>Máximo 5 días hábiles</td></tr>
                <tr><td>Ventana de mantenimiento programado</td><td>Sábados 10 PM – 2 AM, previo aviso 48 h</td></tr>
                <tr><td>Garantía por fase entregada (corrección sin costo)</td><td>30 días calendario desde la entrega</td></tr>
            </tbody>
        </table>
        <div class="contract-alert info">
            <h3>Definición de "Error Crítico"</h3>
            Se entiende por error crítico cuando el Sistema completo es inaccesible o la función principal (registro de surtido) está bloqueada para todos los usuarios. Lentitud, errores de visualización o módulos secundarios no constituyen error crítico.
        </div>
    </section>

    <section class="contract-section">
        <h2>Cláusula Novena — Limitación de Responsabilidad</h2>
        <p><strong>9.1</strong> La responsabilidad total del Prestador por cualquier causa derivada de este contrato no excederá el monto total efectivamente pagado por el Cliente bajo la fase vigente y, si aplica, por los costos operacionales o servicios post-garantía de los <strong>3 meses previos</strong> al evento generador del daño.</p>
        <p><strong>9.2</strong> En ningún caso el Prestador será responsable por: (a) daños indirectos, incidentales o consecuenciales; (b) pérdida de ganancias, de clientes o de rutas; (c) daños causados por uso inadecuado del Sistema; (d) fallas en servicios de terceros (Internet, APIs externas, energía eléctrica); (e) pérdida de datos causada por el propio Cliente.</p>
        <p><strong>9.3</strong> El Cliente es responsable de mantener copias de seguridad de su información operativa fuera del Sistema.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Décima — Confidencialidad</h2>
        <p><strong>10.1</strong> Ambas partes se obligan a guardar estricta <strong>confidencialidad</strong> sobre toda información a la que accedan en virtud de este contrato, incluyendo datos de rutas, clientes, precios, inventarios, conductores y estrategia operativa.</p>
        <p><strong>10.2</strong> Esta obligación de confidencialidad se extiende por <strong>3 años</strong> después de la terminación del contrato.</p>
        <p><strong>10.3</strong> El Prestador no podrá compartir, vender ni publicar datos del Cliente sin autorización expresa y escrita.</p>
        <p><strong>10.4</strong> Excepción: información que sea de dominio público o que deba divulgarse por orden judicial o autoridad competente.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Undécima — Duración, Terminación y Mora</h2>
        <p><strong>11.1</strong> El contrato inicia el <strong>16 de abril de 2026</strong> y permanecerá vigente hasta la entrega y cierre de las fases expresamente contratadas por el Cliente, incluyendo el periodo de garantía aplicable a cada una.</p>
        <p><strong>11.2 Terminación por el Cliente:</strong> El Cliente podrá dar por terminada la mensualidad operativa de la fase activa o el soporte post-garantía con aviso escrito de 15 días calendario, pagando únicamente los valores causados, aprobados o ya ejecutados hasta la fecha efectiva de terminación.</p>
        <p><strong>11.3 Terminación por el Prestador:</strong> Si el Cliente incumple el pago por más de 10 días hábiles respecto de facturas aprobadas de fases, mensualidad operativa o servicios post-garantía, el Prestador podrá suspender el componente afectado. Si el incumplimiento persiste por 30 días adicionales, el contrato podrá darse por terminado respecto de los servicios pendientes no pagados.</p>
        <p><strong>11.4 Mora:</strong> El no pago oportuno generará un interés moratorio del 1,5% mensual sobre el saldo en mora.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Duodécima — Protección de Datos Personales</h2>
        <p>El tratamiento de datos personales de conductores, clientes y demás personas vinculadas se realizará conforme a la <strong>Ley 1581 de 2012</strong> y sus decretos reglamentarios. El Cliente es el Responsable del Tratamiento. El Prestador actúa como Encargado y se compromete a implementar medidas técnicas y organizativas adecuadas para la protección de los datos.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Decimotercera — Modificaciones al Contrato</h2>
        <p>Cualquier modificación, adición o prórroga de este contrato requerirá <strong>acuerdo escrito firmado por ambas partes</strong>. Las comunicaciones verbales o por mensajería instantánea no constituyen modificación contractual.</p>
    </section>

    <section class="contract-section">
        <h2>Cláusula Decimocuarta — Jurisdicción y Ley Aplicable</h2>
        <p>Este contrato se rige por la <strong>legislación colombiana</strong>. Para la resolución de cualquier controversia derivada del presente contrato, las partes se someten a la jurisdicción de los jueces y tribunales de <strong>Medellín, Antioquia</strong>, renunciando expresamente a cualquier otro fuero que pudiera corresponderles.</p>
    </section>

    <section class="contract-section contract-closing">
        <div class="contract-alert success">
            <h3>✅ Resumen ejecutivo del contrato</h3>
            Fase 1: $3.800.000 en dos pagos de $1.900.000 · Mensualidad Starter: $290.000/mes por mínimo 3 meses ·
            Garantía correctiva incluida por 30 días · Upgrades abonables al total de $18.144.000 ·
            Revisión 15 días antes del cierre de cada ciclo · Nuevas fases y montos se formalizan por propuesta o anexo ·
            Cada cobro debe generar factura · Soporte post-garantía por horas ·
            Sin fee fijo de transferencia tecnológica · Jurisdicción: Medellín, Antioquia.
        </div>

        <div class="contract-alert info">
            <h3>🖨 Instrucción para firma</h3>
            Este contrato está listo para <strong>imprimir o guardar en PDF</strong>. Una vez firmado, puede ser retornado por correo electrónico o por WhatsApp al Prestador para archivo y formalización.
        </div>

        <h2>Firma</h2>
        <div class="signatures-grid">
            <div class="signature-card">
                <div class="signature-line">
                    @if($contract->status === 'signed' && $signatureImageSrc)
                        <img src="{{ $signatureImageSrc }}" alt="Firma del cliente">
                    @else
                        <span>Firma digital del cliente</span>
                    @endif
                </div>
                <p class="signature-name">{{ $contract->client_signer_name ?? $contract->client_legal_representative }}</p>
                <p class="signature-meta">{{ $contract->client_signer_document ?? $contract->client_legal_representative_document ?? 'Documento pendiente' }}</p>
                <p class="signature-meta">{{ $contract->client_company_name }}</p>
            </div>
            <div class="signature-card">
                <div class="signature-line provider-line">
                    <span>{{ $contract->provider_name }}</span>
                </div>
                <p class="signature-name">{{ $contract->provider_name }}</p>
                <p class="signature-meta">{{ $contract->provider_document ?? 'Prestador de servicios' }}</p>
                <p class="signature-meta">AMR Tech</p>
            </div>
        </div>
        <footer class="contract-footer">
            <p>Este contrato se firma en <strong>dos (2) ejemplares</strong> de igual valor probatorio, en Medellín, Antioquia.</p>
            <p style="margin-top:8px">Contrato No. {{ $contract->contract_number }} · Preparado por AMR Tech · andersonmares81@gmail.com · +57 316 826 5737</p>
        </footer>
    </section>
</article>
