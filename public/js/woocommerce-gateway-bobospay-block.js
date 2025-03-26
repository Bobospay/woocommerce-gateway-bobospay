// const createElement = window.wp.element.createElement;
// const settings = window.wc.wcSettings.getSetting('woocommerce_gateway_bobospay_block_data', {});
// const label = window.wp.htmlEntities.decodeEntities(settings.title);
//
// const Content = () => {
//     return createElement('Fragment', null,
//         createElement('p', null, window.wp.htmlEntities.decodeEntities(settings.description || '')),
//         // createElement('img', {src: settings.icon, className: 'craftgate-card-brands-icon'})
//     )
// };
// const Bobospay = {
//     name: 'woocommerce_gateway_bobospay',
//     label: label,
//     content: Object(window.wp.element.createElement)(Content, null),
//     edit: Object(window.wp.element.createElement)(Content, null),
//     canMakePayment: () => true,
//     ariaLabel: label,
//     supports: {
//         features: settings.supports,
//     },
// };
//
// window.wc.wcBlocksRegistry.registerPaymentMethod(Bobospay);


const createElement = window.wp.element.createElement;
const Fragment = window.wp.element.Fragment;
const settings = window.wc.wcSettings.getSetting('woocommerce_gateway_bobospay_block_data', {});
const label = window.wp.htmlEntities.decodeEntities(settings.title);

const Content = () => {
    return createElement(Fragment, null,
        createElement('p', null, window.wp.htmlEntities.decodeEntities(settings.description || '')),
        settings.icon && createElement('img', {src: settings.icon, className: 'craftgate-card-brands-icon'})
    );
};

const Bobospay = {
    name: 'woocommerce_gateway_bobospay',
    label: label,
    content: createElement(Content, null),
    edit: createElement(Content, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || [],
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Bobospay);
