# -*- coding: utf-8 -*-
"""
Created on Fri Nov 12 12:52:46 2021

@author: John
"""


print("test")
#11/4 added frameshift functionality for nsp 
# from Bio.Seq import SeqIO
# from Bio.SeqRecord import SeqRecord
import sys
import csv

def translate(seq):
      
    table = {
        'ATA':'I', 'ATC':'I', 'ATT':'I', 'ATG':'M',
        'ACA':'T', 'ACC':'T', 'ACG':'T', 'ACT':'T',
        'AAC':'N', 'AAT':'N', 'AAA':'K', 'AAG':'K',
        'AGC':'S', 'AGT':'S', 'AGA':'R', 'AGG':'R',                
        'CTA':'L', 'CTC':'L', 'CTG':'L', 'CTT':'L',
        'CCA':'P', 'CCC':'P', 'CCG':'P', 'CCT':'P',
        'CAC':'H', 'CAT':'H', 'CAA':'Q', 'CAG':'Q',
        'CGA':'R', 'CGC':'R', 'CGG':'R', 'CGT':'R',
        'GTA':'V', 'GTC':'V', 'GTG':'V', 'GTT':'V',
        'GCA':'A', 'GCC':'A', 'GCG':'A', 'GCT':'A',
        'GAC':'D', 'GAT':'D', 'GAA':'E', 'GAG':'E',
        'GGA':'G', 'GGC':'G', 'GGG':'G', 'GGT':'G',
        'TCA':'S', 'TCC':'S', 'TCG':'S', 'TCT':'S',
        'TTC':'F', 'TTT':'F', 'TTA':'L', 'TTG':'L',
        'TAC':'Y', 'TAT':'Y', 'TAA':'_', 'TAG':'_',
        'TGC':'C', 'TGT':'C', 'TGA':'_', 'TGG':'W',
    }
    protein =""
    if len(seq)%3 == 0:
        for i in range(0, len(seq), 3):
            codon = seq[i:i + 3]
            protein+= table[codon]
    return protein



def func(inputtupli):
    # refseq = SeqIO.read("./fastas/reference.fasta","fasta")
    
    with open("../fastas/reference.fasta","r") as ref:
        refseq = ""
        for line in ref: 
                    
            if line.startswith(">"):
                pass:
            else:
                refseq += line[0:-1]



    # print(refseq)


    genedic=dict()
    orfabli = []
    with open('../fastas/genestend.csv', newline='') as csvfile:
        spamreader = csv.reader(csvfile, delimiter=',', quotechar='|')
        next(spamreader)
        for sline in spamreader:
            if (sline[0] != "Non Code" or sline[0] != "ORF10") and sline[0] != ''   :
                genedic[sline[0]] = (int(sline[3]),int(sline[4]),int(sline[1]),int(sline[2]), int(sline[4])-int(sline[3]))
            if sline[0].startswith("NSP"):
                orfabli.append(sline[0])
                
    nofrli = []
    for g in range(1,12):
        nofrli.append("NSP"+str(g))
    print(nofrli)
    protli = []
    ntli = []
    ntprotli= []

    string = ""
    csvli = []
    for item in tupli:
        # print(item          )
        st = int(item[0])
        ed = int(item[1])
        for key in genedic:
            
            if genedic[key][0] < st < genedic[key][1]:
                # print("amino acid coord start",int((st-1)/3),"... gene start AA", genedic[key][3])
                stamino = genedic[key][3] - int((st-1)/3)
                stgene=key
                print("start",key,st)
            if genedic[key][0] < ed < genedic[key][1]:
                # print("amino acid 3' coord start",int((ed)/3),"... gene start AA", genedic[key][3],key)
                edamino = genedic[key][3] - int(ed/3)
                edgene=key
                print("end",key,ed)
        line = [st,ed,stgene,edgene]
        string += str(st)+','+str(ed)+','+stgene+','+edgene+"\n"
        csvli.append(line)

    sline = line
    for i in range(0,4):
        for k in range(0,4):
            
            stmod = refseq[:int(sline[0])-(i)]
            
            edmodprot= refseq[(int(sline[1])-1)+k:int(sline[1])+60]
            edmodnt = refseq[(int(sline[1])-1)+k:int(sline[1])+800]
            edmodntfull = refseq[(int(sline[1])-1)+k:int(sline[1])+300]
            # edmodntfull = refseq[(int(sline[1])-1)+k:]

            
            seqallmod = stmod+edmodprot
            seqallnt = stmod + edmodnt
            last3 = stmod[-6:].seq
            seqallntfull = stmod + edmodntfull
            
            stli = ["nochange",+1,+2,+3]
            edli = ["no change","-1nt", "-2nt","-3nt"]
            # print(sequence)
            if len(seqallmod)%3 != 0:
                seqallmod+"N"
                if len(seqallmod)%3 != 0:
                    seqallmod+"N"
            # if int(sline[0])<(13442-255):
            if sline[2] in nofrli:
                for p in range(0,3):
                    ntseq=seqallnt[-1600:]
                    countc=0
                    countg=0
                    for char in ntseq:
                        if char == "C":
                            countc+=1
                        if char =="G":
                            countg+=1
                    if 264+p == 265:
                        frsh = "5'side not frameshifted"
                    else:
                        frsh = "5'side frameshifted"

                    prot = translate(seqallmod[264+p:])
                    description = "SARS-COV-2 junction {stg},{edg}| {fr}".format(stg=sline[0],edg=sline[1],fr = frsh)
                    id = "SARS-CoV-2|{stg}-{edg}|{stgene}-{edgene}|StFrame={stfr}|EdFrame={edfr}|frameshifted=+{fr}".format(stg=sline[0],edg=sline[1],stfr=i,edfr=k,stgene=sline[2],edgene=sline[3][:-1],fr=p)
                    protli.append((prot[-40:],description,id))
                    
                
                
            # elif 21555>int(sline[0])>(13442-255):
            elif "NSP" in sline[2]:
                
                for p in range(0,3):
                    if 264+p == 264:
                        frsh = "5'side not frameshifted"
                    else:
                        frsh = "5'side frameshifted"
                    ntseq=seqallnt[-1600:]
                    countc=0
                    countg=0
                    for char in ntseq:
                        if char == "C":
                            countc+=1
                        if char =="G":
                            countg+=1
                    
                    
                    
                    prot = translate(seqallmod[264+p:])
                    description = "SARS-COV-2 junction {stg},{edg}| {fr}".format(stg=sline[0],edg=sline[1],fr = frsh)
                    id = "SARS-CoV-2|{stg}-{edg}|{stgene}-{edgene}|StFrame={stfr}|EdFrame={edfr}|frameshifted=+{fr}".format(stg=sline[0],edg=sline[1],stfr=i,edfr=k,stgene=sline[2],edgene=sline[3][:-1],fr=p)
                    protli.append((prot[-40:],description,id))
                
            else:
                # print(sline[2])
                for p in range(0,3):
                    ntseq=seqallnt[-1600:]
                    countc=0
                    countg=0
                    for char in ntseq:
                        if char == "C":
                            countc+=1
                        if char =="G":
                            countg+=1
                    
                    
                    prot = translate(seqallmod[264+p:])
                    description = "SARS-COV-2 junction {stg},{edg}".format(stg=sline[0],edg=sline[1])
                    id = "SARS-CoV-2|{stg}-{edg}|{stgene}-{edgene}|StFrame={stfr}|EdFrame={edfr}|frameshifted=+{fr}".format(stg=sline[0],edg=sline[1],stfr=i,edfr=k,stgene=sline[2],edgene=sline[3][:-1],fr=p)
                    protli.append((prot[-40:],description,id))
    

    print(protli)
    return protli



# tupli = [(sys.argv[1],sys.argv[2])]
if __name__ == "__main__":
    print("inpython")

    tupli = [(sys.argv[1],sys.argv[2])]
    print(tupli)
    func(tupli))
