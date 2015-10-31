var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.SubscribeRowButtonWidget = Y.Base.create('subscribeRowButtonWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.renderStatus();
        },
        destructor: function(){
        },
        renderStatus: function(){
            var tp = this.template,
                subscribe = this.get('subscribe'),
                changeDisable = this.get('changeDisable');

            tp.toggleView(!subscribe, 'on', 'buttons')
            tp.each('on,emailOn,emailOff,bosOn,bosOff', function(node){
                console.log(changeDisable);
                node.set('disabled', changeDisable);
            }, this);
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            owner: NS.Owner.ATTRIBUTE,
            subscribe: {value: null},
            changeDisable: {value: false}
        },
        CLICKS: {}
    });

};