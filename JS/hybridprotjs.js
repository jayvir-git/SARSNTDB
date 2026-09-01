function translate(sequence) {
    const codons = { GCA: 'A',
  GCC: 'A',
  GCG: 'A',
  GCT: 'A',
  TGC: 'C',
  TGT: 'C',
  GAC: 'D',
  GAT: 'D',
  GAA: 'E',
  GAG: 'E',
  TTC: 'F',
  TTT: 'F',
  GGA: 'G',
  GGC: 'G',
  GGG: 'G',
  GGT: 'G',
  CAC: 'H',
  CAT: 'H',
  ATA: 'I',
  ATC: 'I',
  ATT: 'I',
  AAA: 'K',
  AAG: 'K',
  CTA: 'L',
  CTC: 'L',
  CTG: 'L',
  CTT: 'L',
  TTA: 'L',
  TTG: 'L',
  ATG: 'M',
  AAC: 'N',
  AAT: 'N',
  CCA: 'P',
  CCC: 'P',
  CCG: 'P',
  CCT: 'P',
  CAA: 'Q',
  CAG: 'Q',
  AGA: 'R',
  AGG: 'R',
  CGA: 'R',
  CGC: 'R',
  CGG: 'R',
  CGT: 'R',
  AGC: 'S',
  AGT: 'S',
  TCA: 'S',
  TCC: 'S',
  TCG: 'S',
  TCT: 'S',
  ACA: 'T',
  ACC: 'T',
  ACG: 'T',
  ACT: 'T',
  GTA: 'V',
  GTC: 'V',
  GTG: 'V',
  GTT: 'V',
  TGG: 'W',
  TAC: 'Y',
  TAT: 'Y' }
    let res="";

    for (let i = 0; i<sequence.length; i+=3){
        // console.log(sequence.slice(i,i+3));
        let tmpres = codons[sequence.slice(i,i+3)] ;

        // console.log(tmpres);
        if (typeof tmpres === "string"){
            res += tmpres;
        }
        // res += tmpres;


    }



    // console.log(res)
    return res;
}


// console.log(refseq);
// console.log(translate("ATGACC"));

console.log("tst")
function func(inputarray) {
    console.log("akkkk")
    let resli = [];
    for (let i = 0; i<4; i++){
        for (let k = 0; k<4; k++){
            let stmod = refseq.slice(0,inputarray[0]-k);
            let edmodprot = refseq.slice(inputarray[1]-1+i,inputarray[1]+60);
            
            
            let seqallmod = stmod+edmodprot;
           

            
            for (let p = 0; p<3; p++){
                let prot = translate(seqallmod.slice(p));
                // console.log(prot)
                let description = "SARS-CoV-2 | " + inputarray[0] + "--" + inputarray[1] + " | 5' removed = " +i +" | 3' removed =" + k+"| Frame = " +p + " | ";
                resli.push([description,prot.slice(-40)]);
        }}
    
    
    
    
    }
    return resli;
}

// console.log(func([5249,21139]));
