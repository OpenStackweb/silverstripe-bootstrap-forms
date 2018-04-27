<?php

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\View\SSViewer;

class BootstrapFieldList extends Extension {

    /**
     * @param string $name
     * @return mixed|null
     */
	private function getIgnoresByName($name){
        $ignores = $this->owner->getField('ignores');
        if (!$ignores) {
            $ignores = [];
            $this->owner->setField('ignores', $ignores);
        }
        return isset($ignores[$name]) ? $ignores[$name] : null;
    }

    private function setIgnores($name){
        $ignores = $this->owner->getField('ignores');
        if (!$ignores) {
            $ignores = [];

        }
        $ignores[$name] = true;
        $this->owner->setField('ignores', $ignores);
    }
	/**
	 * Transforms all fields in the FieldList to use Bootstrap templates
	 * @return FieldList
	 */
	public function bootstrapify() {		
		foreach($this->owner as $f) {

			$sng = Injector::inst()->get($f->class, true, ['dummy', '']);

            if(!is_null($this->getIgnoresByName($f->getName()))) continue;

            // if we have a CompositeField, bootstrapify its children
            if($f instanceof CompositeField) {
                $f->getChildren()->bootstrapify();
                continue;
            }

			// If we have a Tabset, bootstrapify all Tabs
			if($f instanceof TabSet) {
				$f->Tabs()->bootstrapify();
			}

			// If we have a Tab, bootstrapify all its Fields
			if($f instanceof Tab) {
				$f->Fields()->bootstrapify();
			}

			// If the user has customised the holder template already, don't apply the default one.
			if($sng->getFieldHolderTemplate() == $f->getFieldHolderTemplate()) {
				$template = "Bootstrap{$f->class}_holder";			
				if(SSViewer::hasTemplate($template)) {					
					$f->setFieldHolderTemplate($template);				
				}
				else {				
					$f->setFieldHolderTemplate("BootstrapFieldHolder");
				}

			}

			// If the user has customised the field template already, don't apply the default one.
			if($sng->getTemplate() == $f->getTemplate()) {
				foreach(array_reverse(ClassInfo::ancestry($f)) as $className) {						
					$bootstrapCandidate = "Bootstrap{$className}";
					$nativeCandidate = $className;
					if(SSViewer::hasTemplate($bootstrapCandidate)) {
						$f->setTemplate($bootstrapCandidate);
						break;
					}
					elseif(SSViewer::hasTemplate($nativeCandidate)) {
						$f->setTemplate($nativeCandidate);
						break;
					}


				}
			}
		}

		return $this->owner;		
	}

	/**
	 * Adds this field as ignored. Should not take on boostrap transformation
	 * 
	 * @param  string $field The name of the form field
	 * @return FieldList
	 */
	public function bootstrapIgnore($field) {
		$this->setIgnores($field);
		return $this->owner;
	}
}
