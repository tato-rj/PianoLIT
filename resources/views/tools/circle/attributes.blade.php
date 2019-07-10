@php ($keyObj = $keys->find($key))

id="key-{{str_replace('#', 's', $key)}}" 
control-left="#key-{{$keyObj->next()}}"
control-right="#key-{{$keyObj->prev()}}"

key-major="{{$keyObj->getMajorKey()}}" 
key-minor="{{$keyObj->getMinorKey()}}" 
key-enharmonic-major="{{$keyObj->setEnharmonic()->getMajorKey()}}" 
key-enharmonic-minor="{{$keyObj->setEnharmonic()->getMinorKey()}}" 

key-neighbors="{{$keyObj->setMajor()->getNeighbors()}}"
key-enharmonic-neighbors="{{$keyObj->setEnharmonic()->getNeighbors()}}"

key-major-roman="{{$keyObj->setMajor()->getRomanNumerals()}}"
key-enharmonic-roman="{{$keyObj->setEnharmonic()->getRomanNumerals()}}"
key-minor-roman="{{$keyObj->setMinor()->getRomanNumerals()}}"

key-major-tonic="{{$keyObj->setMajor()->getTonic()}}"  
key-major-dominant="{{$keyObj->setMajor()->getDominant()}}" 
key-major-subdominant="{{$keyObj->setMajor()->getSubdominant()}}" 

key-enharmonic-tonic="{{$keyObj->setEnharmonic()->getTonic()}}"  
key-enharmonic-dominant="{{$keyObj->setEnharmonic()->getDominant()}}" 
key-enharmonic-subdominant="{{$keyObj->setEnharmonic()->getSubdominant()}}" 

key-minor-tonic="{{$keyObj->setMinor()->getTonic()}}" 
key-minor-dominant="{{$keyObj->setMinor()->getDominant()}}" 
key-minor-subdominant="{{$keyObj->setMinor()->getSubdominant()}}"