(window.webpackJsonp=window.webpackJsonp||[]).push([[24],{IJ7u:function(e,t,s){(e.exports=s("I1BE")(!1)).push([e.i,"\n.flow[data-v-5fe31a05] {\n  background-color: #f4f6fa;\n  box-shadow: inset 1px 2px 2px rgba(0, 0, 0, 0.14);\n  border-radius: 8px;\n}\n.box-plus-button[data-v-5fe31a05] {\n  top: -19px;\n}\n.green-box[data-v-5fe31a05] {\n  border: 2px solid #1cbb70;\n}\n.lh0[data-v-5fe31a05] {\n  line-height: 0;\n}\n",""])},KuJD:function(e,t,s){var a=s("IJ7u");"string"==typeof a&&(a=[[e.i,a,""]]);var n={hmr:!0,transform:void 0,insertInto:void 0};s("aET+")(a,n);a.locals&&(e.exports=a.locals)},YWzu:function(e,t,s){"use strict";var a=s("KuJD");s.n(a).a},rrJu:function(e,t,s){"use strict";var a={props:{errors:{type:Object,default:null}}},n=s("KHd+"),r=Object(n.a)(a,(function(){var e=this,t=e.$createElement,s=e._self._c||t;return Object.keys(e.errors).length>0?s("div",[s("p",[e._v("app.error_title")]),e._v(" "),s("br"),e._v(" "),e._l(e.errors,(function(t){return s("ul",{key:t.id},e._l(t,(function(t){return s("li",{key:t.id},[e._v("\n      "+e._s(t)+"\n    ")])})),0)}))],2):e._e()}),[],!1,null,null,null);t.a=r.exports},sCcz:function(e,t,s){"use strict";s.r(t);var a=s("rrJu"),n=s("Z84v"),r=s("+SZM"),o=s("xCLS"),i={components:{Layout:r.a,Errors:a.a,LoadingButton:n.a,Actions:o.default},props:{notifications:{type:Array,default:null}},data:function(){return{numberOfSteps:1,isComplete:!1,numberOfBeforeSteps:0,numberOfAfterSteps:0,oldestStep:0,newestStep:0,form:{name:null,type:null,steps:[],errors:[]},loadingState:"",errorTemplate:Error}},computed:{orderedSteps:function(){return _.orderBy(this.form.steps,"id")}},mounted:function(){this.form.steps.push({id:0,type:"same_day",frequency:"days",number:1,actions:[]})},methods:{notFirstAndLastStep:function(e){return(this.oldestStep!=e||1!=this.numberOfSteps)&&this.newestStep!=e},addStepBefore:function(){this.oldestStep=this.oldestStep+-1,this.form.steps.push({id:this.oldestStep,type:"before",frequency:"days",number:1,actions:[]}),this.numberOfSteps=this.numberOfSteps+1,this.numberOfBeforeSteps=this.numberOfBeforeSteps+1},addStepAfter:function(){this.newestStep=this.newestStep+1,this.form.steps.push({id:this.newestStep,type:"after",frequency:"days",number:1,actions:[]}),this.numberOfSteps=this.numberOfSteps+1,this.numberOfAfterSteps=this.numberOfAfterSteps+1},removeStep:function(e){e.id;this.form.steps.splice(this.form.steps.findIndex((function(t){return t.id===e.id})),1),"before"==e.type&&(this.numberOfSteps=this.numberOfSteps-1,this.numberOfBeforeSteps=this.numberOfBeforeSteps-1,e.id==this.oldestStep&&(this.oldestStep=Math.min.apply(Math,this.form.steps.map((function(e){return e.id}))))),"after"==e.type&&(this.numberOfSteps=this.numberOfSteps-1,this.numberOfAfterSteps=this.numberOfAfterSteps-1,e.id==this.newestStep&&(this.newestStep=Math.max.apply(Math,this.form.steps.map((function(e){return e.id})))))},submit:function(){var e=this;this.loadingState="loading",axios.post("/"+this.$page.auth.company.id+"/account/flows",this.form).then((function(e){localStorage.success="The flow has been added",Turbolinks.visit("/"+e.data.company_id+"/account/flows")})).catch((function(t){e.loadingState=null,e.form.errors=_.flatten(_.toArray(t.response.data))}))},checkComplete:function(e){var t=!0;null==this.form.type&&(t=!1),this.form.name||(t=!1);for(var s=0;s<this.form.steps.length;s++)for(var a=this.form.steps[s].actions,n=0;n<a.length;n++)0!=a[n].complete&&a[n].complete||(t=!1);this.isComplete=t}}},c=(s("YWzu"),s("KHd+")),l=Object(c.a)(i,(function(){var e=this,t=e.$createElement,s=e._self._c||t;return s("layout",{attrs:{title:"Home",notifications:e.notifications}},[s("div",{staticClass:"ph2 ph0-ns"},[s("div",{staticClass:"mt4-l mt1 mw6 br3 bg-white box center breadcrumb relative z-0 f6 pb2"},[s("ul",{staticClass:"list ph0 tc-l tl"},[s("li",{staticClass:"di"},[s("inertia-link",{attrs:{href:"/"+e.$page.auth.company.id+"/dashboard"}},[e._v("\n            "+e._s(e.$page.auth.company.name)+"\n          ")])],1),e._v(" "),s("li",{staticClass:"di"},[e._v("\n          ...\n        ")]),e._v(" "),s("li",{staticClass:"di"},[s("inertia-link",{attrs:{href:"/"+e.$page.auth.company.id+"/account/flows"}},[e._v("\n            "+e._s(e.$t("app.breadcrumb_account_manage_flows"))+"\n          ")])],1),e._v(" "),s("li",{staticClass:"di"},[e._v("\n          "+e._s(e.$t("app.breadcrumb_account_add_employee"))+"\n        ")])])]),e._v(" "),s("div",{staticClass:"mw7 center br3 mb5 bg-white box restricted relative z-1"},[s("div",{staticClass:"pa3 mt5 center"},[s("h2",{staticClass:"tc normal mb4"},[e._v("\n          "+e._s(e.$t("account.flows_cta"))+"\n        ")]),e._v(" "),s("errors",{attrs:{errors:e.form.errors}}),e._v(" "),s("form",{on:{submit:function(t){return t.preventDefault(),e.submit(t)}}},[s("div",{staticClass:"mb3"},[s("label",{staticClass:"db fw4 lh-copy f6",attrs:{for:"name"}},[e._v(e._s(e.$t("account.flow_new_flow")))]),e._v(" "),s("input",{directives:[{name:"model",rawName:"v-model",value:e.form.name,expression:"form.name"}],staticClass:"br2 f5 w-100 ba b--black-40 pa2 outline-0",attrs:{id:"name",type:"text",name:"name",required:""},domProps:{value:e.form.name},on:{change:e.checkComplete,input:function(t){t.target.composing||e.$set(e.form,"name",t.target.value)}}}),e._v(" "),s("p",{staticClass:"f7 mb4 lh-title"},[e._v("\n              "+e._s(e.$t("account.flow_new_help"))+"\n            ")])]),e._v(" "),s("div",{staticClass:"mb3 flow pv4"},e._l(e.orderedSteps,(function(t){return s("div",{key:t.id},[s("div",{directives:[{name:"show",rawName:"v-show",value:e.oldestStep==t.id,expression:"oldestStep == step.id"}],staticClass:"tc lh0"},[s("img",{staticClass:"center pointer",attrs:{src:"/img/company/account/flow_plus_top.svg"},on:{click:function(t){return e.addStepBefore()}}})]),e._v(" "),s("div",{staticClass:"step tc measure center bg-white br3 ma3 mt0 mb0 relative",class:{"green-box":e.numberOfSteps>1&&"same_day"==t.type}},[s("img",{directives:[{name:"show",rawName:"v-show",value:"same_day"!=t.type,expression:"step.type != 'same_day'"}],staticClass:"box-plus-button absolute br-100 pa2 bg-white pointer",attrs:{src:"/img/trash_button.svg"},on:{click:function(s){return s.preventDefault(),e.removeStep(t)}}}),e._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:"before"==t.type,expression:"step.type == 'before'"}],staticClass:"condition pa3 bb bb-gray"},[s("ul",{staticClass:"list ma0 pa0 mb2"},[s("li",{staticClass:"di mr2"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.number,expression:"step.number"}],staticClass:"tc br2 f5 ba b--black-40 pa2 outline-0",attrs:{id:"",type:"number",min:"1",max:"100",name:"",required:""},domProps:{value:t.number},on:{input:function(s){s.target.composing||e.$set(t,"number",s.target.value)}}})]),e._v(" "),s("li",{staticClass:"di mr2"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.frequency,expression:"step.frequency"}],staticClass:"mr1",attrs:{id:"frequency_days_"+t.id,type:"radio",name:"frequency_before_"+t.id,value:"days"},domProps:{checked:e._q(t.frequency,"days")},on:{change:function(s){return e.$set(t,"frequency","days")}}}),e._v(" "),s("label",{attrs:{for:"frequency_days_"+t.id}},[e._v(e._s(e.$t("account.flow_new_days")))])]),e._v(" "),s("li",{staticClass:"di mr2"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.frequency,expression:"step.frequency"}],staticClass:"mr1",attrs:{id:"frequency_weeks_"+t.id,type:"radio",name:"frequency_before_"+t.id,value:"weeks"},domProps:{checked:e._q(t.frequency,"weeks")},on:{change:function(s){return e.$set(t,"frequency","weeks")}}}),e._v(" "),s("label",{attrs:{for:"frequency_weeks_"+t.id}},[e._v(e._s(e.$t("account.flow_new_weeks")))])]),e._v(" "),s("li",{staticClass:"di"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.frequency,expression:"step.frequency"}],staticClass:"mr1",attrs:{id:"frequency_months_"+t.id,type:"radio",name:"frequency_before_"+t.id,value:"months"},domProps:{checked:e._q(t.frequency,"months")},on:{change:function(s){return e.$set(t,"frequency","months")}}}),e._v(" "),s("label",{attrs:{for:"frequency_months_"+t.id}},[e._v(e._s(e.$t("account.flow_new_months")))])])]),e._v(" "),s("p",{staticClass:"ma0 pa0"},[e._v("\n                    "+e._s(e.$t("account.flow_new_before"))+" "),s("span",{staticClass:"brush-blue"},[e._v(e._s(e.$t("account.flow_new_type_"+e.form.type)))])])]),e._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:"same_day"==t.type,expression:"step.type == 'same_day'"}],staticClass:"condition pa3 bb bb-gray"},[s("p",{staticClass:"ma0 pa0 mb2"},[e._v("\n                    "+e._s(e.$t("account.flow_new_the_day_event_happens"))+"\n                  ")]),e._v(" "),s("select",{directives:[{name:"model",rawName:"v-model",value:e.form.type,expression:"form.type"}],on:{change:[function(t){var s=Array.prototype.filter.call(t.target.options,(function(e){return e.selected})).map((function(e){return"_value"in e?e._value:e.value}));e.$set(e.form,"type",t.target.multiple?s:s[0])},e.checkComplete]}},[s("option",{attrs:{value:"employee_joins_company"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_joins_company"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_leaves_company"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_leaves_company"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_birthday"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_birthday"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_joins_team"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_joins_team"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_leaves_team"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_leaves_team"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_becomes_manager"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_becomes_manager"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_new_position"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_new_position"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_leaves_holidays"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_leaves_holidays"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_returns_holidays"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_returns_holidays"))+"\n                    ")]),e._v(" "),s("option",{attrs:{value:"employee_returns_leave"}},[e._v("\n                      "+e._s(e.$t("account.flow_new_type_employee_returns_leave"))+"\n                    ")])])]),e._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:"after"==t.type,expression:"step.type == 'after'"}],staticClass:"condition pa3 bb bb-gray"},[s("ul",{staticClass:"list ma0 pa0 mb2"},[s("li",{staticClass:"di mr2"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.number,expression:"step.number"}],staticClass:"tc br2 f5 ba b--black-40 pa2 outline-0",attrs:{id:"",type:"number",min:"1",max:"100",name:"",required:""},domProps:{value:t.number},on:{input:function(s){s.target.composing||e.$set(t,"number",s.target.value)}}})]),e._v(" "),s("li",{staticClass:"di mr2"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.frequency,expression:"step.frequency"}],staticClass:"mr1",attrs:{id:"frequency_days_"+t.id,type:"radio",name:"frequency_after_"+t.id,value:"days"},domProps:{checked:e._q(t.frequency,"days")},on:{change:function(s){return e.$set(t,"frequency","days")}}}),e._v(" "),s("label",{attrs:{for:"frequency_days_"+t.id}},[e._v(e._s(e.$t("account.flow_new_days")))])]),e._v(" "),s("li",{staticClass:"di mr2"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.frequency,expression:"step.frequency"}],staticClass:"mr1",attrs:{id:"frequency_weeks_"+t.id,type:"radio",name:"frequency_after_"+t.id,value:"weeks"},domProps:{checked:e._q(t.frequency,"weeks")},on:{change:function(s){return e.$set(t,"frequency","weeks")}}}),e._v(" "),s("label",{attrs:{for:"frequency_weeks_"+t.id}},[e._v(e._s(e.$t("account.flow_new_weeks")))])]),e._v(" "),s("li",{staticClass:"di"},[s("input",{directives:[{name:"model",rawName:"v-model",value:t.frequency,expression:"step.frequency"}],staticClass:"mr1",attrs:{id:"frequency_months_"+t.id,type:"radio",name:"frequency_after_"+t.id,value:"months"},domProps:{checked:e._q(t.frequency,"months")},on:{change:function(s){return e.$set(t,"frequency","months")}}}),e._v(" "),s("label",{attrs:{for:"frequency_months_"+t.id}},[e._v(e._s(e.$t("account.flow_new_months")))])])]),e._v(" "),s("p",{staticClass:"ma0 pa0"},[e._v("\n                    "+e._s(e.$t("account.flow_new_after"))+" "),s("span",{staticClass:"brush-blue"},[e._v(e._s(e.$t("account.flow_new_type_"+e.form.type)))])])]),e._v(" "),s("actions",{on:{completed:function(t){return e.checkComplete(t)}},model:{value:t.actions,callback:function(s){e.$set(t,"actions",s)},expression:"step.actions"}})],1),e._v(" "),e.notFirstAndLastStep(t.id)?s("div",{staticClass:"tc lh0"},[s("img",{staticClass:"center pointer",attrs:{src:"/img/company/account/flow_line.svg"}})]):e._e(),e._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:e.newestStep==t.id,expression:"newestStep == step.id"}],staticClass:"tc"},[s("img",{staticClass:"center pointer",attrs:{src:"/img/company/account/flow_plus_bottom.svg"},on:{click:function(t){return e.addStepAfter()}}})])])})),0),e._v(" "),s("div",{staticClass:"mv4"},[s("div",{staticClass:"flex-ns justify-between"},[s("div",[s("a",{staticClass:"btn btn-secondary dib tc w-auto-ns w-100 mb2 pv2 ph3",attrs:{href:"/"+e.$page.auth.company.id+"/account/employees"}},[e._v(e._s(e.$t("app.cancel")))])]),e._v(" "),s("loading-button",{attrs:{classes:"btn add w-auto-ns w-100 mb2 pv2 ph3",state:e.loadingState,text:e.$t("app.save"),"cypress-selector":"submit-add-employee-button"}})],1)])])],1)])])])}),[],!1,null,"5fe31a05",null);t.default=l.exports}}]);
//# sourceMappingURL=24.js.map?id=693db74c0fd18d0419e7