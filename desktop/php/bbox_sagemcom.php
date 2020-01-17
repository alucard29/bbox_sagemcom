<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'bbox_sagemcom');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter votre box}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach (eqLogic::byType('bbox_sagemcom') as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend>{{Mes BBox}}
        </legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
                <center>
                    <i class="fa fa-plus-circle" style="font-size : 7em;color:#2ca7d7;"></i>
                </center>
                <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;;color:#2ca7d7"><center>Ajouter</center></span>
            </div>
            <?php
            foreach (eqLogic::byType('bbox_sagemcom') as $eqLogic) {
                echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                echo "<center>";
                echo '<img src="plugins/bbox_sagemcom/doc/images/bbox_sagemcom_icon.png" height="105" width="95" />';
                echo "</center>";
                echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
  <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
  <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
			  <br/>
				
				<form class="form-horizontal">
					<fieldset>
						<legend>{{Général}}</legend>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Nom à afficher}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la box Serveur}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" >{{Objet parent}}</label>
							<div class="col-sm-3">
								<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
									<option value="">{{Aucun}}</option>
									<?php
									foreach (jeeObject::all() as $object) {
										echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Catégorie}}</label>
							<div class="col-sm-8">
								<?php
								foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
									echo '<label class="checkbox-inline">';
									echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
									echo '</label>';
								}
								?>

							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" ></label>
							<div class="col-sm-9">
								<!--<input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable" checked/> -->
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
								<!--<input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible" checked/> -->
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
							</div>
						</div>
					</fieldset> 
					<legend>{{Configuration}}</legend>
					<div class="form-group">
						<label class="col-sm-3 control-label" >{{Adresse réseau}}</label>
						<div class="col-sm-3">
							<input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="BBOX_SERVER_IP" placeholder="{{bbox}}"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" >{{Mode}}</label>
						<div class="col-sm-3">
							<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="BBOX_USE_API">
								<option value="api">{{API}}</option>
								<option value="default">{{défaut}}</option>
							</select>
						</div>
						<div id="note1" class="col-sm-3">
							<i>Choisir API si votre version de firmware est supérieure ou égale à 10.0.58</i> 
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" >{{Connexion}}</label>
						<div class="col-sm-3">
							<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="BBOX_CONNEXION_TYPE">
								<option value=0>{{Cable}}</option>
								<option value=1>{{xDSL/FTTH}}</option>
							</select>
						</div>
					</div>
					<div id="box_passwd" class="form-group">
						<label class="col-sm-3 control-label" >{{Mot de passe}}</label>
						<div class="col-sm-3">
							<input class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="BBOX_PSSWD" type="password" placeholder="{{saisir le password}}"/>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label" >{{Appliquer la tuile Pré-configurée}}</label>
						<div class="col-sm-3">
							<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="BBOX_CUSTOM_WIDGET" checked/></label>
						</div>
						<div id="note2" class="col-sm-3">
							<i>Note : cette case sera remise à Non après application des paramètres. Vous n'aurez à réappliquer cette case que si vous faites des modifications et voulez revenir à l'affichage préconfiguré.</i> 
						</div>
					</div>
					<br>
				</form>
				<br>
				
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!--<legend>{{Commandes}}</legend>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-actions">
							<a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
							<a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
						</div>
					</fieldset>
				</form>-->
				<br>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<!--<th style="width: 7%;">{{Id}}</th>
							<th style="width: 20%;">{{Nom}}</th>
							<th style="width: 7%;">{{Type}}</th>
							<th style="width: 7%;">{{Sous-Type}}</th>
							<th style="width: 25%;">{{Note}}</th>
							<th style="width: 23%;">{{Paramètres}}</th>
							<th style="width: 6%;">{{Action}}</th>-->
							<th>{{Id}}</th>
							<th>{{Nom}}</th>
							<th>{{Type}}</th>
							<th>{{Sous-Type}}</th>
							<th>{{Note}}</th>
							<th>{{Paramètres}}</th>
							<th>{{Action}}</th>
						</tr>
					</thead>
					<tbody>

					</tbody>
				</table>
				<!--<form class="form-horizontal">
					<fieldset>
						<div class="form-actions">
							<a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
							<a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
						</div>
					</fieldset>
				</form>-->
			</div>
		</div>
    </div>
</div>

<?php include_file('desktop', 'bbox_sagemcom', 'js', 'bbox_sagemcom'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
