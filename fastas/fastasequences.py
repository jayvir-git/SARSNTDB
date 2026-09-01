import csv 

genedic=dict()
orfabli = []
with open('C:/Users/John/Documents/GitHub/Covid_jorgera/coviddesktop/Covidproj/genestend.csv', newline='') as csvfile:
    spamreader = csv.reader(csvfile, delimiter=',', quotechar='|')
    next(spamreader)
    for sline in spamreader:
        if (sline[0] != "Non Code" or sline[0] != "ORF10") and sline[0] != ''   :
            genedic[sline[0]] = [int(sline[3]),int(sline[4]),int(sline[1]),int(sline[2]), int(sline[4])-int(sline[3])]
        if sline[0].startswith("NSP"):
            orfabli.append(sline[0])

print(genedic)
refseq=""
with open(r"C:\xampp\htdocs\php_su\fastas\reference.fasta","r") as fasta:
    for line in fasta:
        if line.startswith(">"):
            pass
        else: 
            refseq+= line.strip()

print(refseq)


for item in genedic:
    genedic[item].append(refseq[genedic[item][0]-1:genedic[item][1]])

print(genedic)

with open("ntseqtable.csv","w") as tabl:
    string= ""
    for item in genedic:
        string+=item
        string+=","
        string+= genedic[item][5]
        string+= ",\n"