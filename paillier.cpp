#include <iostream>
#include <gmpxx.h>

#include <cstdlib> //TEMPORARY
#include <cstring> //TEMPORARY

//mpz_mul(pub->n_squared, pub->n, pub->n);// = n ^ 2
//mpz_add_ui(pub->n_plusone, pub->n, 1); // = n + 1

class paillier {
public:
	mpz_t publicKey;// = n
	mpz_t privateKey;// = lambda

	paillier() : paillier(1024) { }

	paillier(int bits) {
		mpz_t p;
		mpz_t q;
		modulusbits = bits;
		mpz_init(publicKey);
		mpz_init(privateKey);
		mpz_init(p);
		mpz_init(q);

		gmp_randstate_t rand;
		init_rand(rand, modulusbits / 8 + 1);
		do {
			do
				mpz_urandomb(p, rand, modulusbits / 2);
			while( !mpz_probab_prime_p(p, 10) );
			do
				mpz_urandomb(q, rand, modulusbits / 2);
			while( !mpz_probab_prime_p(q, 10) );
			mpz_mul(publicKey, p, q);
		} while( !mpz_tstbit(publicKey, modulusbits - 1) );

		mpz_sub_ui(p, p, 1);
		mpz_sub_ui(q, q, 1);
		mpz_lcm(privateKey, p, q);

		mpz_clear(p);
		mpz_clear(q);
	  gmp_randclear(rand);
	}


  mpz_class encrypt(mpz_t m, mpz_t publicK) {
  	mpz_t c;
	  mpz_t r;
	  gmp_randstate_t rand;
	  mpz_t x;
	  mpz_t np1;
	  mpz_t n2;

	  mpz_init(x);
	  mpz_init(c);
	  mpz_init(r);
	  mpz_init(np1);
	  mpz_init(n2);
 	  init_rand(rand, modulusbits / 8 + 1);
	  do
		  mpz_urandomb(r, rand, modulusbits);
  	while(mpz_cmp(r, publicK) >= 0);

	  mpz_add_ui(np1, publicK, 1);
	  mpz_mul(n2, publicK, publicK);
  	mpz_powm(c, np1, m, n2);
	  mpz_powm(x, r, publicK, n2);
	  mpz_mul(c, c, x);
	  mpz_mod(c, c, n2);

	  mpz_clear(x);
	  mpz_clear(r);
    gmp_randclear(rand);

		return mpz_class(c);
  }

  mpz_class decrypt(mpz_t c, mpz_t publicK, mpz_t privateK) {
		mpz_t m;
		mpz_t np1;
		mpz_t n2;
		mpz_t x;
		mpz_init(m);
		mpz_init(np1);
		mpz_init(n2);
		mpz_init(x);

		mpz_add_ui(np1, publicK, 1);
		mpz_mul(n2, publicK, publicK);

		mpz_powm(x, np1, privateK, n2);
		mpz_sub_ui(x, x, 1);
		mpz_div(x, x, publicK);
		mpz_invert(x, x, publicK);

		mpz_powm(m, c, privateK, n2);
		mpz_sub_ui(m, m, 1);
		mpz_div(m, m, publicK);
		mpz_mul(m, m, x);
		mpz_mod(m, m, publicK);

		return mpz_class(m);
	}

	mpz_class sum(mpz_t publicK, mpz_t a, mpz_t b) {
		mpz_t c;
		mpz_t n2;
		mpz_init(c);
		mpz_init(n2);

		mpz_mul(n2, publicK, publicK);

		mpz_mul(c, a, b);
		mpz_mod(c, c, n2);
		return mpz_class(c);
	}

	mpz_class mul(mpz_t publicK, mpz_t a, mpz_t b) { // b - NOT encrypted
		mpz_t c;
		mpz_t n2;
		mpz_init(c);
		mpz_init(n2);

		mpz_mul(n2, publicK, publicK);

		mpz_powm(c, a, b, n2);
		return mpz_class(c);
	}

private:
	int modulusbits;



	void init_rand(gmp_randstate_t rand, int bytes) {
	void* buf;
	mpz_t s;

	buf = malloc(bytes);
	paillier_get_rand_file(buf, bytes, "/dev/urandom");

	gmp_randinit_default(rand);
	mpz_init(s);
	mpz_import(s, bytes, 1, 1, 0, 0, buf);
	gmp_randseed(rand, s);
	mpz_clear(s);
	free(buf);
}

void paillier_get_rand_file(void* buf, int len, const char* file) {
	FILE* fp;
	void* p;
	fp = fopen(file, "r");
	p = buf;
	while( len )
	{
		size_t s;
		s = fread(p, 1, len, fp);
		p += s;
		len -= s;
	}
	fclose(fp);
}

};



int main(int argc, char** argv) {
	paillier* P = new paillier();

	mpz_class publicKey = mpz_class(P->publicKey);
	mpz_class privateKey = mpz_class(P->privateKey);

	mpz_class A = mpz_class("123456789");
	mpz_class B = mpz_class("876403210");
	mpz_class D = mpz_class("2");

	mpz_class eA = P->encrypt(A.get_mpz_t(), publicKey.get_mpz_t());
	mpz_class eB = P->encrypt(B.get_mpz_t(), publicKey.get_mpz_t());
	mpz_class eD = P->encrypt(D.get_mpz_t(), publicKey.get_mpz_t());
	mpz_class eC = P->sum(P->publicKey, eA.get_mpz_t(), eB.get_mpz_t());
	mpz_class eE = P->mul(P->publicKey, eA.get_mpz_t(), D.get_mpz_t());
	mpz_class dC = P->decrypt(eC.get_mpz_t(), publicKey.get_mpz_t(), privateKey.get_mpz_t());
	mpz_class dE = P->decrypt(eE.get_mpz_t(), publicKey.get_mpz_t(), privateKey.get_mpz_t());



	std::cout << "A=" << A << std::endl << "B=" << B << std::endl;
	std::cout << "C(A + B)="<< (A + B) << std::endl;
	std::cout << "dC      =" << dC << std::endl;
	
	std::cout << "E(A * D)="<< (A * D) << std::endl;
	std::cout << "dE      =" << dE << std::endl;
}



/*
randomize: function(a) {
		var rn;
		if (this.rncache.length > 0) {
			rn = this.rncache.pop();
		} else {
			rn = this.getRN();
		}
		return (a.multiply(rn)).mod(this.n2);
	},
	getRN: function() {
		var r, rng = new SecureRandom();
		do {
			r = new BigInteger(this.bits,rng);
			// make sure r <= n
		} while(r.compareTo(this.n) >= 0);
		return r.modPow(this.n, this.n2);
	}
//*/




/*
void complete_prvkey( paillier_prvkey_t* prv, paillier_pubkey_t* pub )
{
	mpz_powm(prv->x, pub->n_plusone, prv->lambda, pub->n_squared);
	mpz_sub_ui(prv->x, prv->x, 1);
	mpz_div(prv->x, prv->x, pub->n);
	mpz_invert(prv->x, prv->x, pub->n);
}//*/





