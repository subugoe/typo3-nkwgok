#!/usr/bin/env python
#coding=utf-8

import urllib
from lxml import etree
recordCount = 500

baseURL = 'http://opac.sub.uni-goettingen.de/DB=1/XML=1/CMD?ACT=BRWS&SCNST=' + recordCount + 'TRM=lkl+'
scanNext = 'a'
saveCount = 0

while scanNext:
	URL = baseURL + scanNext
	xmlString = urllib.urlopen(URL).read()
	
	fileName = 'hitcounts-' + scanNext + '.xml'
	scanNext = None
	if xmlString:
		xml = etree.fromstring(xmlString)
		if xml:
			hitCountFile = open(fileName, 'w')
			hitCountFile.write(xmlString)
			hitCountFile.close()
			saveCount += recordCount
			print 'Saved ' recordCount + ' LKL hitcounts to ' + fileName
			
			nextXML = xml.xpath('/RESULT/SCANNEXT/@term')
			if len(nextXML) > 0:
				scanNext = nextXML[0]
			else:
				print 'Finished (no SCANNEXT/@term element found)'
				print 'about ' + saveCount + ' records downloaded'
		else:
			print 'Could not parse XML from ' + URL
	else:
		print 'Could not read data from ' + URL

