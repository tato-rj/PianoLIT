@php ($keyObj = $keys->find($key))

id="key-{{str_replace('#', 's', $key)}}" 
control-left="#key-{{$keyObj->next()}}"
control-right="#key-{{$keyObj->prev()}}"

key-major="{{$keyObj->getMajorKey()}}" 
key-minor="{{$keyObj->getMinorKey()}}" 

key-neighbors="{{$keyObj->getNeighbors()}}"

key-major-roman="{{$keyObj->setMajor()->getRomanNumerals()}}"
key-minor-roman="{{$keyObj->setMinor()->getRomanNumerals()}}"

key-major-tonic="{{$keyObj->setMajor()->getTonic()}}"  
key-major-dominant="{{$keyObj->setMajor()->getDominant()}}" 
key-major-subdominant="{{$keyObj->setMajor()->getSubdominant()}}" 
key-major-negative="{{$keyObj->setMajor()->getNegative()}}"

key-minor-tonic="{{$keyObj->setMinor()->getTonic()}}" 
key-minor-dominant="{{$keyObj->setMinor()->getDominant()}}" 
key-minor-subdominant="{{$keyObj->setMinor()->getSubdominant()}}"
key-minor-negative="{{$keyObj->setMinor()->getNegative()}}"