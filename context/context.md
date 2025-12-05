Rutiar
Rutiar tiene en su MVP dos apps:
- App Web Administrativa
- App móvil para conductores

Sigue el siguiente modelo de negocio:
Una compañía de expresos  institucionales contrata el servicio.
Se crea el registro de la compañía dentro de la base de datos a través de un comando que solo puede ejecutarse desde el core de la app.
Se crea el usuario de la compañía y se envía un correo con las credenciales automáticas.
En su primer login lo debe obligar a cambiar la contraseña.
La compañía agrega socios a la plataforma.
La compañía o el socio puede agregar conductores, es importante que si el socio necesita más conductores de los que incluye la app sea la compañía la que autorice la creación de nuevos conductores.
La compañía puede hacer rutas.
Estos roles son:
SUPERADMIN - Administrador general de la app
COMPANY_ADMIN - Administrador de la compañía y dueño de la cuenta.
PARTNER - Socio que está asociado a la compañía
DRIVER - Conductor que está asociado al Socio.
CLIENT - Pasajero
CORPORATIVE - Cliente corporativo que entrega a la compañía los pasajeros.
El corporativo manda constantemente la lista de pasajeros (normalmente cada semana)
Este puede hacer cambios entre semana.
La compañía crea una ruta basada en la zona a la que la mayoría de clientes está apuntando.
Luego con estas ruta crea pequeños runs para los socios dónde define los pasajeros y si es de entrada o salida, la hora de llegada, el costo y a que socios está asociada esta ruta.
Una ruta puede tener varios runs asociados a un socio, ej. Ruta Guasmo Entrada 08:00 AM -> Socio 1 y Socio 2 cada una tendrá una lista de pasajeros diferente.
Un socio toma la ruta asignada y define que conductor hará esa ruta (de los que tiene asociado).
El conductor o el socio debe definir el orden de recolección y la hora en la que pasará por el pasajero (el sistema puede sugerir pero debe poder ser flexible) esto lo puede hacer el socio también desde la app web, el conductor solo usará la app móvil.
A una hora determinada todos los runs para el día se aprueban, si hay un cambio en la lista de pasajeros o necesitan redefinir algo, se aplicará al día siguiente al de la run.
Los runs se deben poder compartir para que los pasajeros sepan a que hora pasará el bus por ellos.
La compañía puede acceder a estadísticas necesarias pero la más importante es la generación de prefacturas dónde pueden ver por socio cuanto se debe pagar.
Los socios pueden acceder a estadísticas necesarias pero la más importante es poder tener un historial de sus facturas.
Los conductores puedes ver estadísticas en su app movil pero la más importante es ver sus rutas.
Los corporativos pueden acceder a ciertos datos pero esto en la versión 2.

En la app móvil solo pueden acceder los conductores, esta tiene las siguientes características:
La app tiene una diagramación sencilla, y pocas opciones.
La opción Mis Ruta es la principal donde está la ruta activa, debe existir un botón para definir el orden de los pasajeros y otro para iniciar la ruta.
La otra opción es el historial de rutas.
Cuando presiona definir el orden ve la lista de pasajeros en orden con la dirección escrita, puede seleccionar el mapa para ver la ruta de forma gráfica, para cambiar basta con arrastrar o subir-bajar un nombre, así como la hora de recogida, aqui se valida que las horas sean válidas y sean menores a la hora que debe llegar al destino. Esto solo se debe hacer en la entrada, en la salida normalmente no importa el orden de llegada.
Cuando presiona iniciar ruta se iniciará la ruta del día (depende de la hora busca la ruta más cercana por ejemplo si a las 8 am debo estar en la planta y son las 5 entonces uso esa ruta para ese día, como el orden lo defino de manera general pues se crea una instancia de ese dato de ese día).
La aplicación va recolectando el trayecto seguido junto con la velocidad. También debe detectar cuando el carro esté más de 1 min detenido como parada y cuanto tiempo estuvo parado.
Cuando llegue al primer punto de recogida debe de haber un botón en la pantalla que diga recoger, al presionarlo aparece un cronómetro que espera el minuto (normalmente esto es configurable por la compañía) y si se pasa mostrarlo en rojo.
Si la persona llega debe presionar un botón de asistencia. Si la persona no sale y falta debe presionar un botón de falta que abrirá la cámara y permitirá tomar una foto como prueba de la espera.
Siempre habrá un indicador en la pantalla que muestre cuantas personas van recogiendo.
Al terminar el recorrido y llegar a la fábrica presiona Terminar. Esto se sincroniza con el servidor de forma que si se llega a quedar sin señal la información sea completamente manejable.
La app también debe permitir el reportar incidentes y terminar anticipadamente un viaje, normalmente esto se soluciona de manera diferente, pero en la prefectura debería haber un punto dónde se muestren los incidentes y permita establecer descuentos.

Esta es la base para luego utilizarlo con transporte escolar.

Rutas:
1. La ruta general se crea una vez (GUASMO)
2. La definición se crea al inicio (GUASMO 10PM Entrada 20 USD Socio 01 Conductor 01 y lista de pasajeros) 
3. El conductor ordena sus listas asignadas y pone el tiempo en el que va a recoger a la persona. 
4. A diario se crea un snapshot que es el que se encarga de leer la app móvil para saber: A quien debe recoger, en que orden y a que hora aproximada, cuanto está costando esa ruta, y un snapshop del orden para trazabilidad (este campo debe ser json) 
5. Los eventos del movil se almacenan basados en el snapshopt. 
6. Si la compañía asigna una nueva definición al socio, este se le notifica para que asigne el conductor y este organice la lista de personas. 
7. Si una definición cambia pero no cambia el socio asociado o la lista de pasajeros pues se notifica a las personas asociadas lo que cambió por ejemplo el valor) 
8. Si una definición cambia el socio asociado se debe notificar al socio anterior para que sepa y al n nuevo socio para que asigne al conductor. 
9. Si la definición cambia algo en los pasajeros (alguien entra o sale) se debe notificar al conductor para que vuelva a ordenar los pasajeros.
