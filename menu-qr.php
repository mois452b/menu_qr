<?php
/*
Plugin Name: Menu-QR
Description: Plugin para listar los platos de menus
Author: Moises Rodriguez
Version: 1.0.0
*/

/**
 *  [
 *       bebidas => [
 *           "refrescos" => '5$',
 *           'jugo natural' => '3$'
 *       ],
 *       postres => [
 *          'torta' => '7$'
 *       ]
 *  ]
 */
function show_menu( $attrs ) {
    $menu = wp_remote_get('http://localhost/wp-json/menu_qr/v1/menu')['body'];
    $menu = json_decode($menu, true);
    ob_start( );
    ?>
    <style>
        .display-flex {
            display: flex;
            width: 100%
        }
        .align-center {
            text-align: center;
        }
        .separator {
            margin: 0 15px;
            white-space: nowrap; 
            overflow: hidden; 
        }
        .food {
            white-space: nowrap; 
        }
    </style>
    <div class="align-center">
        <h2 class="align-center"> <?= $menu['title'] ?> </h2>
        <ul class="align-center">
        <?php foreach( $menu['elements'] as $food => $price ): ?>
                <li class="display-flex" >
                    <div class="food"><?= $food; ?></div>
                    <div class="separator">···············································································································································································································································································································································
                    </div>
                    <div><?= $price; ?>$</div>
                </li>
        <?php endforeach?>
        </ul>
    </div>
    <?php
    return ob_get_clean( );
}

function admin_menu( ) {
    ob_start( );
    ?>
    <div>
        <input type="button" id="agregar" value="Añadir" >
        <input type="text" id="title" >
        <div id="dinamic" ></div>
        <input type="button" id="save" value="guardar" >
    </div>
    <script>
        // Constantes para el div contenedor de los inputs y el botón de agregar
        const contenedor = document.querySelector('#dinamic');
        const btnAgregar = document.querySelector('#agregar');
        const btnSave = document.querySelector('#save');
        const title = document.querySelector('#title');

        // Variable para el total de elementos agregados
        let total = 1;

        /**
         * Método que se ejecuta cuando se da clic al botón de agregar elementos
         */
        btnAgregar.addEventListener('click', e => {
            let div = document.createElement('div');
            div.innerHTML = `<label>${total++}</label> - <input type="text" name="nombre[]" required><input type="number" value=0 ><button onclick="eliminar(this)">Eliminar</button>`;
            contenedor.appendChild(div);
        })

        btnSave.addEventListener('click', e => {
            let foods = [];
            let prices = [];
            let elements_foods = document.querySelectorAll('#dinamic input[type=text]').forEach( element => foods.push( element.value ) );
            let elements_prices = document.querySelectorAll('#dinamic input[type=number]').forEach( element => prices.push( element.value ) );
            let elements = {};
            foods.forEach( ( food, index ) => elements[food] = prices[index] )
            let data = {
                title: title.value,
                elements: elements
            }
            console.log(data)
            fetch('http://localhost/wp-json/menu_qr/v1/menu', {
                method: "POST", // *GET, POST, PUT, DELETE, etc.
                // mode: "cors", // no-cors, *cors, same-origin
                // cache: "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
                // credentials: "same-origin", // include, *same-origin, omit
                headers: {
                "Content-Type": "application/json",
                },
                // redirect: "follow", // manual, *follow, error
                // referrerPolicy: "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
                body: JSON.stringify(data) // body data type must match "Content-Type" header
            })
            .then( response => console.log(response) )
            
        })

        /**
         * Método para eliminar el div contenedor del input
         * @param {this} e 
         */
        const eliminar = (e) => {
            const divPadre = e.parentNode;
            contenedor.removeChild(divPadre);
            actualizarContador();
        };

        /**
         * Método para actualizar el contador de los elementos agregados
        */
        const actualizarContador = () => {
            let divs = contenedor.children;
            total = 1;
            for (let i = 0; i < divs.length; i++) {
                divs[i].children[0].innerHTML = total++;
            }//end for
        };
    </script>
    <?php
    return ob_get_clean( );
}

function get_menu( ) {
    $menu = array(
        [
            'title' => 'Bebidas',
            'elements' => [
                'refrescos' => '5$',
                'jugo natural' => '3$',
                'gaseosa' => '2$',
            ]
        ],
        'postres' => [
            'torta' => '5$',
            'pudin' => '4$'
        ]
    );
    // return $menu[0];
    return get_option('menu', array('title'=>'','elements'=>[]));
}

function save_menu( $data ) {
    $menu = [
        'title' => $data['title'],
        'elements' => $data['elements']
    ];
    update_option('menu', $menu);
    return $data['title'];
}

function add_custom_MQR_endpoints( ) {
    register_rest_route('menu_qr/v1', '/menu', array(
        'methods' => 'GET',
        'callback' =>'get_menu',
        'permission_callback' => function( ) {
            return true;
        }
    ) );
    register_rest_route('menu_qr/v1', '/menu', array(
        'methods' => 'POST',
        'callback' =>'save_menu',
        'permission_callback' => function( ) {
            return true;
        }
    ) );
}

function MQR_init( ) {
    add_shortcode('show_menu', 'show_menu');
    add_shortcode('admin_menu', 'admin_menu');
    add_action('rest_api_init', 'add_custom_MQR_endpoints');
}

add_action('init', 'MQR_init');
